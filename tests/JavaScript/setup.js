/**
 * Jest setup file for FreePOS JavaScript tests
 */

// Setup DOM environment
require('@testing-library/jest-dom');

// Mock common browser globals that are used in POS
global.$ = global.jQuery = jest.fn(() => ({
    on: jest.fn(),
    off: jest.fn(),
    trigger: jest.fn(),
    html: jest.fn(),
    val: jest.fn(),
    show: jest.fn(),
    hide: jest.fn(),
    dialog: jest.fn(),
    prop: jest.fn(),
    attr: jest.fn(),
    find: jest.fn(),
    css: jest.fn(),
    addClass: jest.fn(),
    removeClass: jest.fn(),
    append: jest.fn(),
    prepend: jest.fn(),
    children: jest.fn(),
    parent: jest.fn(),
    each: jest.fn(),
    ready: jest.fn()
}));

// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock console methods to reduce noise in tests
global.console = {
    ...console,
    log: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
    info: jest.fn(),
    debug: jest.fn(),
};

// Reset all mocks before each test
beforeEach(() => {
    jest.clearAllMocks();
});