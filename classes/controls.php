<?php

declare(strict_types=1);

class Controls
{
    /**
     * Sanitize string input (strips tags and encodes special characters)
     */
    public static function sanitizeString(string $str): string
    {
        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+
        // Using htmlspecialchars instead for similar functionality
        return htmlspecialchars(strip_tags($str), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize integer input
     */
    public static function sanitizeInt(mixed $int): int
    {
        return (int)filter_var($int, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Generate HTML select box for resources
     */
    public static function ResourceSelectBox(): string
    {
        $output = '<select name="resource" class="resource-box">';
        foreach (RESOURCES as $resource) {
            $output .= '<option value="' . htmlspecialchars($resource) . '">' . htmlspecialchars($resource) . '</option>';
        }
        $output .= '</select>';
        return $output;
    }

    /**
     * Generate HTML select box for skill gems
     *
     * @param string $name Name attribute for the select element
     * @param string $selected Currently selected skill gem
     */
    public static function SkillGemSelectBox(string $name, string $selected = ""): string
    {
        $output = '<select name="' . htmlspecialchars($name) . '" class="skillgem-box">';
        foreach (SKILL_GEMS as $skillgem => $details) {
            $selected_attr = ($skillgem === $selected) ? ' selected' : '';
            $output .= '<option' . $selected_attr . ' value="' . htmlspecialchars($skillgem) . '">' . htmlspecialchars($skillgem) . '</option>';
        }
        $output .= '</select>';
        return $output;
    }
}
