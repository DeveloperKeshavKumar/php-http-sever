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

        // Process conditional statements (<% if %>, <% else %>, <% endif %>)
        $content = $this->processConditionals($content);

        // Process loops (<% for %>, <% foreach %>, <% while %>)
        $content = $this->processLoops($content);

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
            // Add $ prefix to variables in the expression
            $expression = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);

            // Convert dot notation (e.g., user.name) to array access syntax (e.g., $user['name'])
            $expression = preg_replace_callback('/(\$\w+)(?:\.(\w+))+/', function ($subMatches) {
                // Split the dot notation into parts (e.g., $user.name.email becomes $user['name']['email'])
                $parts = explode('.', $subMatches[0]);
                $variable = array_shift($parts); // The first part is the variable (e.g., $user)
                $result = $variable;
                foreach ($parts as $part) {
                    $result .= "['$part']";
                }
                return $result;
            }, $expression);

            return "<?php echo htmlspecialchars($expression, ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);
    }

    /**
     * Process unescaped syntax (<%- %>).
     * Outputs the raw value without escaping.
     */
    private function processUnescaped(string $content): string
    {
        return preg_replace_callback('/<%-+\s*(.+?)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the expression
            $expression = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);
            return "<?php $expression; ?>";
        }, $content);
    }

    /**
     * Process conditional statements (<% if %>, <% else %>, <% endif %>).
     */
    private function processConditionals(string $content): string
    {
        // Process if statements
        // Process if statements
        $content = preg_replace_callback('/<%+\s*if\s*\((.+?)\)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the condition
            $condition = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);
            return "<?php if ($condition): ?>";
        }, $content);

        // Process elseif statements
        $content = preg_replace_callback('/<%+\s*elseif\s*\((.+?)\)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the condition
            $condition = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);
            return "<?php elseif ($condition): ?>";
        }, $content);

        // Process else statements
        $content = preg_replace('/<%+\s*else\s*%>/', '<?php else: ?>', $content);

        // Process endif statements
        $content = preg_replace('/<%+\s*endif\s*%>/', '<?php endif; ?>', $content);

        return $content;
    }

    private function processLoops(string $content): string
    {
        // Process for loops
        $content = preg_replace_callback('/<%+\s*for\s*\((.+?)\)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the loop condition
            $condition = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);
            return "<?php for ($condition): ?>";
        }, $content);

        // Process foreach loops
        $content = preg_replace_callback('/<%+\s*foreach\s*\((.+?)\s+as\s+([a-zA-Z_]\w*)\)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the loop condition, but preserve the "as" keyword
            $condition = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)(?!\s+as\b)/', '$$1', $matches[1]);
            $variable = '$' . $matches[2]; // Correctly reference the loop variable
            return "<?php foreach ($condition as $variable): ?>";
        }, $content);

        // Process while loops
        $content = preg_replace_callback('/<%+\s*while\s*\((.+?)\)\s*%>/', function ($matches) {
            // Add $ prefix to variables in the loop condition
            $condition = preg_replace('/(\b[a-zA-Z_]\w*\b)(?!=)/', '$$1', $matches[1]);
            return "<?php while ($condition): ?>";
        }, $content);

        // Process endfor, endforeach, and endwhile
        $content = preg_replace('/<%+\s*endfor\s*%>/', '<?php endfor; ?>', $content);
        $content = preg_replace('/<%+\s*endforeach\s*%>/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/<%+\s*endwhile\s*%>/', '<?php endwhile; ?>', $content);

        return $content;
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