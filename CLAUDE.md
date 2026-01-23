# Code Style for PHP

- Adhere strictly to the PSR-12 coding standard.
- Always use strict types (declare(strict_types=1);) at the top of PHP files.
- Ensure all functions, methods, and properties have explicit type declarations.
- Use early returns to keep the code structure clean and easy to read (happy path).
- Document with PHPDoc only for generics or complex array shapes that native type hints cannot cover.
- Practice DRY (Do Not Repeat Yourself) coding practices
- Views go into pages/
- Code for views (i.e. controllers) go into code/ with the same file name as the matching view
- classes/ are a mixture of models and tooling and should always be lower case
- func contains common functions that are one-off tools to maintain DRY

# Code Style for Javascript

- Use vanilla javascript, do not import modules or libraries
- Place reusable pieces of javascript, such as functions, in the public/js directory

# Code Style for HTML & CSS

- Use vanilla HTML and CSS where possible
- Place css customizations in the public/css/custom.css file
