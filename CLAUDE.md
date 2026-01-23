# Code Style
### Code Style for PHP
- Adhere strictly to the PSR-12 coding standard.
- Always use strict types (declare(strict_types=1);) at the top of PHP files.
- Ensure all functions, methods, and properties have explicit type declarations.
- Use early returns to keep the code structure clean and easy to read (happy path).
- Document with PHPDoc only for generics or complex array shapes that native type hints cannot cover.
- DRY (Do Not Repeat Yourself) coding practices
- Views go into pages/
- Code for views (i.e. controllers) go into code/ with the same file name as the matching view
- classes/ are a mixture of models and tooling and should always be lower case
- func contains common functions that are one-off tools to maintain DRY

### Code Style for Javascript
- Use vanilla javascript, do not import modules or libraries
- Place reusable pieces of javascript, such as functions, in the public/js directory
- DRY (Do Not Repeat Yourself) coding practices

### Code Style for HTML & CSS
- Use vanilla HTML and CSS where possible
- Place css customizations in the public/css/custom.css file

# General Rules
### Database
- NEVER update the .sql files
- NEVER attempt to modify the underlying database structure directly. Instead, ask the user to perform structural changes.
- ALWAYS try to use existing functions that call the database before using raw SQL in the DAL class
- ALWAYS use bound parameters to update the database as designed in DAL.php

### Performance
- All views, api calls, code paths, and so forth that are not in the crons directory should return in less than 200ms.
- Lists over 50 items must always be paginated
- Images MUST use next/image with explicit dimensions. Cumulative Layout Shift kills us.

### Security
- ALWAYS check that the system adheres to the OWASP Top 10
- ALWAYS Filter all user input for correctness and safety
