<?php
namespace PhpHttpServer\Template;

interface TemplateInterface {
    /**
     * Render a template with the provided data.
     *
     * @param string $template The template file name (e.g., "example.grd").
     * @param array $data An associative array of data to pass to the template.
     * @return string The rendered template content.
     */
    public function render(string $template, array $data = []): string;

    /**
     * Set the base directory where templates are stored.
     *
     * @param string $templateDir The path to the templates directory.
     * @return void
     */
    public function setTemplateDir(string $templateDir): void;

    /**
     * Get the base directory where templates are stored.
     *
     * @return string The path to the templates directory.
     */
    public function getTemplateDir(): string;
}