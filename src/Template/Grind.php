<?php

namespace PhpHttpServer\Template;

class Grind implements TemplateInterface
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->setTemplateDir($templateDir);
    }

    public function render(string $template, array $data = []): string
    {
        // Ensure the template file has a .grd extension
        if (substr($template, -4) !== '.grd') {
            throw new \Exception("Template file must have a .grd extension: $template");
        }

        $templatePath = $this->templateDir . $template;

        // Check if the template file exists
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: $templatePath");
        }

        // Preprocess the template
        $tempFilePath = $this->preprocessTemplate($templatePath);

        // Extract data into variables
        extract($data);

        // Start output buffering
        ob_start();
        include $tempFilePath;
        $output = ob_get_clean();

        // Clean up the temporary file
        unlink($tempFilePath);

        return $output;
    }

    private function preprocessTemplate(string $templatePath): string
    {
        $content = file_get_contents($templatePath);

        // Process escaped syntax (<%= %>)
        $content = $this->processEscaped($content);

        // Process unescaped syntax (<%- %>)
        $content = $this->processUnescaped($content);

        // Save the preprocessed content to a temporary file
        $tempFilePath = __DIR__ . '/temp/' . uniqid('grind_', true) . '.php';
        file_put_contents($tempFilePath, $content);

        return $tempFilePath;
    }

    /**
     * Process escaped syntax (<%= %>).
     * Escapes the output using htmlspecialchars.
     */
    private function processEscaped(string $content): string
    {
        return preg_replace_callback('/<%=+\s*(.+?)\s*%>/', function ($matches) {
            return "<?php echo htmlspecialchars(\$" . $matches[1] . ", ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);
    }

    /**
     * Process unescaped syntax (<%- %>).
     * Outputs the raw value without escaping.
     */
    private function processUnescaped(string $content): string
    {
        return preg_replace_callback('/<%-+\s*(.+?)\s*%>/', function ($matches) {
            return "<?php echo \$" . $matches[1] . "; ?>";
        }, $content);
    }

    public function setTemplateDir(string $templateDir): void
    {
        $this->templateDir = rtrim($templateDir, '/') . '/';
    }

    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }
}