<?php

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use App\Utility\JsonValidate;

/**
 * Unit tests for input validation and data integrity
 * Tests the real JsonValidate validation class used throughout FreePOS
 */
class ValidationRulesTest extends TestCase
{
    public function testJsonValidateBasicValidation()
    {
        // Test valid data with JsonValidate
        $validData = (object)[
            'name' => 'Valid Item Name',
            'email' => 'test@example.com',
            'price' => '29.99'
        ];
        
        $schema = '{"name":"", "email":"@", "price":"1"}';
        $validator = new JsonValidate($validData, $schema);
        
        $result = $validator->validate();
        $this->assertTrue($result);
    }

    public function testJsonValidateEmailValidation()
    {
        // Test email validation
        $validEmailData = (object)['email' => 'test@example.com'];
        $invalidEmailData = (object)['email' => 'invalid-email'];
        
        $emailSchema = '{"email":"@"}';
        
        $validValidator = new JsonValidate($validEmailData, $emailSchema);
        $this->assertTrue($validValidator->validate());
        
        $invalidValidator = new JsonValidate($invalidEmailData, $emailSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('must be a valid email address', $result);
    }

    public function testJsonValidateNumericValidation()
    {
        // Test numeric validation
        $validNumericData = (object)['price' => '29.99', 'quantity' => '5'];
        $invalidNumericData = (object)['price' => 'abc', 'quantity' => '5'];
        
        $numericSchema = '{"price":"1", "quantity":"1"}';
        
        $validValidator = new JsonValidate($validNumericData, $numericSchema);
        $this->assertTrue($validValidator->validate());
        
        $invalidValidator = new JsonValidate($invalidNumericData, $numericSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('must be numeric', $result);
    }

    public function testJsonValidateRequiredFields()
    {
        // Test required field validation
        $completeData = (object)['name' => 'Test', 'code' => 'ITEM001'];
        $incompleteData = (object)['name' => 'Test']; // Missing code
        
        $requiredSchema = '{"name":"", "code":""}';
        
        $completeValidator = new JsonValidate($completeData, $requiredSchema);
        $this->assertTrue($completeValidator->validate());
        
        $incompleteValidator = new JsonValidate($incompleteData, $requiredSchema);
        $result = $incompleteValidator->validate();
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('code must be specified', $result);
    }

    public function testJsonValidateBlankFields()
    {
        // Test blank field validation
        $validData = (object)['name' => 'Valid Name'];
        $blankData = (object)['name' => ''];
        
        $blankSchema = '{"name":""}';
        
        $validValidator = new JsonValidate($validData, $blankSchema);
        $this->assertTrue($validValidator->validate());
        
        $blankValidator = new JsonValidate($blankData, $blankSchema);
        $result = $blankValidator->validate();
        $this->assertNotTrue($result);
        $this->assertIsString($result);
        $this->assertStringContainsString('must not be blank', $result);
    }

    public function testJsonValidateArrayValidation()
    {
        // Test array validation
        $validArrayData = (object)['items' => [1, 2, 3]];
        $invalidArrayData = (object)['items' => []];
        $notArrayData = (object)['items' => 'not array'];
        
        $arraySchema = '{"items":"["}';
        
        $validValidator = new JsonValidate($validArrayData, $arraySchema);
        $this->assertTrue($validValidator->validate());
        
        $invalidValidator = new JsonValidate($invalidArrayData, $arraySchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('must be an array with at least one value', $result);
        
        $notArrayValidator = new JsonValidate($notArrayData, $arraySchema);
        $result = $notArrayValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('must be an array with at least one value', $result);
    }

    public function testJsonValidateObjectValidation()
    {
        // Test object validation
        $validObjectData = (object)['config' => (object)['setting' => 'value']];
        $invalidObjectData = (object)['config' => 'not object'];
        
        $objectSchema = '{"config":"{"}';
        
        $validValidator = new JsonValidate($validObjectData, $objectSchema);
        $this->assertTrue($validValidator->validate());
        
        $invalidValidator = new JsonValidate($invalidObjectData, $objectSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('must be a json object', $result);
    }

    public function testJsonValidateOptionalNumericFields()
    {
        // Test optional numeric validation (-1 allows empty or numeric)
        $validOptionalData = (object)['optional_number' => '123'];
        $validEmptyData = (object)['optional_number' => ''];
        $invalidOptionalData = (object)['optional_number' => 'abc'];
        
        $optionalSchema = '{"optional_number":"-1"}';
        
        $validValidator = new JsonValidate($validOptionalData, $optionalSchema);
        $this->assertTrue($validValidator->validate());
        
        $validEmptyValidator = new JsonValidate($validEmptyData, $optionalSchema);
        $this->assertTrue($validEmptyValidator->validate());
        
        $invalidValidator = new JsonValidate($invalidOptionalData, $optionalSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('must be numeric', $result);
    }

    public function testCustomerRegistrationValidation()
    {
        // Test real customer registration validation schema (similar to CustomerAccess.php)
        $validCustomerData = (object)[
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'suburb' => 'Downtown',
            'postcode' => '12345',
            'state' => 'CA',
            'country' => 'USA',
            'pass' => 'securepass123',
            'captcha' => 'abc123'
        ];
        
        // Schema from CustomerAccess.php register method
        $customerSchema = '{"name":"", "email":"@", "address":"", "suburb":"", "postcode":"", "state":"", "country":"", "pass":"", "captcha":""}';
        
        $validator = new JsonValidate($validCustomerData, $customerSchema);
        $result = $validator->validate();
        $this->assertTrue($result);
        
        // Test invalid email
        $invalidEmailCustomer = clone $validCustomerData;
        $invalidEmailCustomer->email = 'invalid-email';
        
        $invalidValidator = new JsonValidate($invalidEmailCustomer, $customerSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('must be a valid email address', $result);
    }

    public function testPasswordResetValidation()
    {
        // Test password reset validation schema (from CustomerAccess.php)
        $validResetData = (object)[
            'email' => 'test@example.com',
            'captcha' => 'abc123'
        ];
        
        $resetSchema = '{"email":"@","captcha":""}';
        
        $validator = new JsonValidate($validResetData, $resetSchema);
        $result = $validator->validate();
        $this->assertTrue($result);
        
        // Test missing captcha
        $missingCaptchaData = (object)['email' => 'test@example.com'];
        
        $invalidValidator = new JsonValidate($missingCaptchaData, $resetSchema);
        $result = $invalidValidator->validate();
        $this->assertNotTrue($result);
        $this->assertStringContainsString('captcha must be specified', $result);
    }

    public function testRealJSONValidation()
    {
        // Test actual JSON validation (not the custom helper method)
        $validJSON = '{"name":"test","value":123}';
        $invalidJSON = '{"name":"test",}'; // Trailing comma makes it invalid
        
        // Test json_decode directly
        $validDecoded = json_decode($validJSON);
        $this->assertNotNull($validDecoded);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
        
        $invalidDecoded = json_decode($invalidJSON);
        $this->assertNull($invalidDecoded);
        $this->assertNotEquals(JSON_ERROR_NONE, json_last_error());
    }
}