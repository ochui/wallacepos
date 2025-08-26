/**
 * Unit tests for POSUtil functions
 */

// Mock POS global object
global.POS = {
    getConfigTable: jest.fn(() => ({
        general: {
            dateformat: 'y-m-d'
        }
    }))
};

// Import the utilities code - we'll need to eval it since it's not a module
const fs = require('fs');
const path = require('path');
const utilitiesCode = fs.readFileSync(
    path.join(__dirname, '../../public/assets/js/pos/utilities.js'), 
    'utf8'
);

// Execute the utilities code to make POSUtil available
eval(utilitiesCode);

describe('POSUtil', () => {
    let posUtil;

    beforeEach(() => {
        posUtil = new POSUtil();
        jest.clearAllMocks();
    });

    describe('getDateFromTimestamp', () => {
        test('should format timestamp with default y-m-d format', () => {
            const timestamp = 1640995200000; // 2022-01-01 00:00:00 UTC
            const result = posUtil.getDateFromTimestamp(timestamp);
            
            // The result should include year-month-day format
            expect(result).toMatch(/^\d{4}-\d{1,2}-\d{1,2} \d{2}:\d{2}:\d{2}$/);
        });

        test('should format timestamp with d/m/y format', () => {
            POS.getConfigTable.mockReturnValue({
                general: { dateformat: 'd/m/y' }
            });
            
            const timestamp = 1640995200000; // 2022-01-01 00:00:00 UTC
            const result = posUtil.getDateFromTimestamp(timestamp, 'd/m/y');
            
            // The result should include day/month/year format
            expect(result).toMatch(/^\d{1,2}\/\d{1,2}\/\d{2} \d{2}:\d{2}:\d{2}$/);
        });

        test('should format timestamp with m/d/y format', () => {
            const timestamp = 1640995200000; // 2022-01-01 00:00:00 UTC
            const result = posUtil.getDateFromTimestamp(timestamp, 'm/d/y');
            
            // The result should include month/day/year format
            expect(result).toMatch(/^\d{1,2}\/\d{1,2}\/\d{2} \d{2}:\d{2}:\d{2}$/);
        });

        test('should use custom format when provided', () => {
            const timestamp = 1640995200000;
            const customFormat = 'custom-format';
            const result = posUtil.getDateFromTimestamp(timestamp, customFormat);
            
            // Since custom format is not d/m/y or m/d/y, should default to y-m-d
            expect(result).toMatch(/^\d{4}-\d{1,2}-\d{1,2} \d{2}:\d{2}:\d{2}$/);
        });
    });

    describe('getShortDate', () => {
        test('should return short date without time with y-m-d format', () => {
            POS.getConfigTable.mockReturnValue({
                general: { dateformat: 'y-m-d' }
            });
            
            const timestamp = 1640995200000; // 2022-01-01 00:00:00 UTC
            const result = posUtil.getShortDate(timestamp);
            
            expect(result).toMatch(/^\d{4}-\d{1,2}-\d{1,2}$/);
        });

        test('should return current date when no timestamp provided', () => {
            POS.getConfigTable.mockReturnValue({
                general: { dateformat: 'y-m-d' }
            });
            
            const result = posUtil.getShortDate(null);
            
            // Should return a date string in the expected format
            expect(result).toMatch(/^\d{4}-\d{1,2}-\d{1,2}$/);
        });

        test('should format with d/m/y when configured', () => {
            POS.getConfigTable.mockReturnValue({
                general: { dateformat: 'd/m/y' }
            });
            
            const timestamp = 1640995200000;
            const result = posUtil.getShortDate(timestamp);
            
            expect(result).toMatch(/^\d{1,2}\/\d{1,2}\/\d{2}$/);
        });
    });

    describe('parseDateString', () => {
        test('should parse seconds correctly', () => {
            const result = posUtil.parseDateString('+5 second');
            expect(result).toBe(5000); // 5 seconds = 5000 milliseconds
        });

        test('should parse minutes correctly', () => {
            const result = posUtil.parseDateString('+10 minute');
            expect(result).toBe(600000); // 10 minutes = 600000 milliseconds
        });

        test('should parse hours correctly', () => {
            const result = posUtil.parseDateString('+2 hours');
            expect(result).toBe(7200000); // 2 hours = 7200000 milliseconds
        });

        test('should parse days correctly', () => {
            const result = posUtil.parseDateString('+1 days');
            expect(result).toBe(86400000); // 1 day = 86400000 milliseconds
        });

        test('should parse weeks correctly', () => {
            const result = posUtil.parseDateString('+1 weeks');
            expect(result).toBe(604800000); // 1 week = 604800000 milliseconds
        });

        test('should parse months correctly', () => {
            const result = posUtil.parseDateString('+1 months');
            expect(result).toBe(2.62974e9); // 1 month ≈ 2.62974e9 milliseconds
        });

        test('should parse years correctly', () => {
            const result = posUtil.parseDateString('+1 years');
            expect(result).toBe(3.15569e10); // 1 year ≈ 3.15569e10 milliseconds
        });

        test('should handle negative values (removes sign)', () => {
            // The function actually removes the sign with slice(1), so -1 becomes 1
            const result = posUtil.parseDateString('-1 days');
            expect(result).toBe(86400000); // Actually returns positive value due to slice(1)
        });
    });
});