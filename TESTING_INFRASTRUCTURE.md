# FreePOS Testing Infrastructure Setup Guide

## Problem Solved

The original issue was that tests could not proceed due to authentication and database connection problems. The FreePOS application required:

1. **Database Connection**: Tests needed a working database with proper schema and seeded data
2. **Authentication Setup**: Tests needed to handle user authentication for protected routes
3. **Test Data Seeding**: Tests needed consistent test data using the existing TestData utility

## Solution Implemented

### 1. Test Database Manager (`tests/Support/TestDatabaseManager.php`)

- **SQLite In-Memory Database**: Uses fast SQLite in-memory database for testing
- **Schema Conversion**: Converts MySQL schema to SQLite-compatible schema
- **Automatic Seeding**: Seeds database with test users, categories, items, locations, devices, customers, and tax rules
- **Reset Functionality**: Cleans and re-seeds database between tests for isolation

**Key Features:**
- Admin user: `username: admin, password: admin` (hashed: `8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918`)
- Staff user: `username: staff, password: staff` (hashed: `1562206543da764123c21bd524674f0a8aaf49c8a89744c97352fe677f7e4006`)
- Complete test data including items, categories, suppliers, locations, devices, customers, tax rules

### 2. Base Test Case (`tests/Support/BaseTestCase.php`)

- **Environment Setup**: Properly configures $_SERVER, $_SESSION, and environment variables
- **Authentication Helpers**: Methods to simulate logged-in admin/staff/guest users
- **Database Helpers**: Methods to interact with test database
- **Output Capture**: Helper to capture controller output for JSON response testing
- **JSON Assertions**: Methods to test JSON responses from controllers

**Helper Methods:**
```php
$this->actingAsAdmin();      // Simulate admin login
$this->actingAsStaff();      // Simulate staff login  
$this->actingAsGuest();      // Clear authentication
$this->getTestDatabase();    // Get database connection
$this->getRecords($sql);     // Query test database
$this->captureOutput($fn);   // Capture echoed output
$this->assertJsonResponse(); // Test JSON responses
```

### 3. Enhanced Bootstrap (`tests/bootstrap.php`)

- **Environment Variables**: Loads `.env.testing` for test configuration
- **Session Management**: Initializes session for authentication testing
- **Server Variables**: Sets up proper $_SERVER variables for HTTP request simulation
- **Testing Flag**: Sets environment flag to indicate testing mode

### 4. Environment Configuration (`.env.testing`)

- **Database Settings**: Configured for SQLite in-memory testing
- **Test Flags**: Enables testing mode
- **Timezone**: UTC for consistent test results

## Usage Examples

### 1. Database Connection and Seeding Test

```php
class DatabaseConnectionTest extends BaseTestCase
{
    public function testDatabaseConnectionEstablished()
    {
        $db = $this->getTestDatabase();
        $this->assertNotNull($db);
        
        // Test basic query
        $result = $db->query("SELECT 1 as test")->fetch();
        $this->assertEquals(1, $result['test']);
    }
    
    public function testBasicTestDataSeeded()
    {
        // Check seeded users
        $users = $this->getRecords("SELECT * FROM auth");
        $this->assertGreaterThan(0, count($users));
        
        // Check admin exists
        $admin = $this->getRecords("SELECT * FROM auth WHERE username = 'admin'");
        $this->assertCount(1, $admin);
        $this->assertEquals(1, $admin[0]['admin']);
    }
}
```

### 2. Authentication Integration Test

```php
class AuthenticationIntegrationTest extends BaseTestCase
{
    public function testUserCanLoginWithValidCredentials()
    {
        // Setup login request
        $this->createPostData([
            'data' => json_encode([
                'username' => 'admin',
                'password' => 'admin'
            ])
        ]);

        $authController = new AuthController();
        
        // Capture output since controller echoes JSON
        [$result, $output] = $this->captureOutput(function() use ($authController) {
            return $authController->authenticate();
        });

        // Test success response
        $this->assertStringContainsString('OK', $output);
        $response = json_decode($output, true);
        $this->assertEquals('OK', $response['errorCode']);
    }
    
    public function testSessionAuthenticationWithAdmin()
    {
        $this->actingAsAdmin();
        
        $auth = new Auth();
        $this->assertTrue($auth->isLoggedIn());
        $this->assertTrue($auth->isAdmin());
    }
}
```

### 3. POS Controller Testing with Authentication

```php
class PosControllerTest extends BaseTestCase
{
    public function testGetConfigRequiresAuthentication()
    {
        // Test without authentication
        $this->actingAsGuest();
        
        $controller = new PosController();
        [$result, $output] = $this->captureOutput(function() use ($controller) {
            return $controller->getConfig();
        });
        
        $this->assertErrorResponse('Access Denied', $output);
    }
    
    public function testGetConfigWithAuthentication()
    {
        // Test with authentication
        $this->actingAsAdmin();
        
        $controller = new PosController();
        [$result, $output] = $this->captureOutput(function() use ($controller) {
            return $controller->getConfig();
        });
        
        $this->assertSuccessResponse($output);
    }
}
```

## Running Tests

```bash
# Install dependencies (if not already done)
composer install

# Run all tests
composer test

# Run only database tests
php vendor/bin/phpunit tests/Feature/Database/

# Run only authentication tests  
php vendor/bin/phpunit tests/Feature/Authentication/

# Run with coverage
composer test-coverage
```

## Test Results

✅ **Database Connection**: Tests can now connect to in-memory SQLite database  
✅ **Authentication Setup**: Tests can simulate admin/staff/guest user sessions  
✅ **Test Data Seeding**: Database is automatically seeded with consistent test data  
✅ **Controller Testing**: Controllers can be tested with proper authentication and database context  
✅ **JSON Response Testing**: Controller outputs can be captured and tested  
✅ **Test Isolation**: Each test gets a clean database state  

## Key Files Created/Modified

1. **`tests/Support/TestDatabaseManager.php`** - SQLite database setup and seeding
2. **`tests/Support/BaseTestCase.php`** - Base class with authentication and database helpers
3. **`tests/bootstrap.php`** - Enhanced bootstrap with proper environment setup
4. **`.env.testing`** - Test environment configuration
5. **`tests/Feature/Database/DatabaseConnectionTest.php`** - Database connection verification
6. **`tests/Feature/Authentication/AuthenticationIntegrationTest.php`** - Authentication testing
7. **`tests/Feature/Database/TestDataSeedingTest.php`** - TestData utility verification

## Benefits

1. **No External Dependencies**: Uses SQLite in-memory database - no MySQL setup required
2. **Fast Tests**: In-memory database provides very fast test execution
3. **Consistent Data**: Each test gets the same seeded data state
4. **Authentication Ready**: Built-in support for testing authenticated routes
5. **Real Integration**: Tests use actual FreePOS classes and database interactions
6. **Easy to Use**: Simple helper methods for common testing patterns
7. **Comprehensive Coverage**: Can test all controller routes, database models, and business logic

This infrastructure solves the original problem where "tests cannot proceed due to an authentication issue" and provides a solid foundation for testing all FreePOS features and routes as requested.