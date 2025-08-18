<?php

namespace App\Controllers;

/**
 * ViewController handles serving template files for admin and customer interfaces
 */
class ViewController
{
    /**
     * Serve a template file from the resources/view directory
     * 
     * @param string $section The section (admin or customer)
     * @param string $template The template name
     * @return void
     */
    public function serveTemplate(string $section, string $template): void
    {
        // Sanitize inputs
        $section = preg_replace('/[^a-z]/', '', strtolower($section));
        $template = preg_replace('/[^a-z0-9_-]/', '', strtolower($template));

        die(sprintf("Serving template: %s/%s\n", $section, $template));
        // Construct template path
        $templatePath = base_path("resources/view/{$section}/{$template}.php");
        
        // Check if template exists
        if (!file_exists($templatePath)) {
            http_response_code(404);
            echo "Template not found: {$section}/{$template}";
            return;
        }
        
        // Set appropriate headers
        header('Content-Type: text/html; charset=utf-8');
        
        // Include the template file
        include $templatePath;
    }
    
    /**
     * Handle admin content requests
     * 
     * @param string $template The template name
     * @return void
     */
    public function adminContent(string $template): void
    {
        $this->serveTemplate('admin', $template);
    }
    
    /**
     * Handle customer content requests
     * 
     * @param string $template The template name
     * @return void
     */
    public function customerContent(string $template): void
    {
        $this->serveTemplate('customer', $template);
    }
}