# Yudha Queue - Laravel Light API Starter Kit

This repository is a Laravel project that serves as an API starter kit, built upon a custom framework named "Laravel Light." Its primary purpose is to accelerate API service development by automating boilerplate code generation and providing standardized utilities.

## Key Differences and Customizations from a Standard Laravel Repository:

While this project is fundamentally a Laravel application, it introduces several significant customizations through "Laravel Light" that differentiate it from a vanilla Laravel setup:

1.  **Blueprint-Driven Code Generation**:
    *   **Custom Blueprints**: The `app/Blueprints` directory contains custom blueprint classes (e.g., `BaseBlueprint`, `StarterBlueprint`). These blueprints act as "recipes" for defining the structure of your API resources, including models, database schemas, relationships, controllers, and seeders.
    *   **Custom Artisan Commands**: The project includes custom Artisan commands (e.g., `php artisan light:make-blueprint`, `php artisan light:blueprint`) that leverage these blueprints to automatically generate various application components. This significantly reduces manual coding for new resources.

2.  **Enhanced Helper Traits**:
    The `app/Helpers` directory houses custom traits that provide extended functionality:
    *   **`LightControllerHelper`**: Offers standardized methods for handling API requests, including consistent validation, and uniform JSON responses for success, errors, and data saving. It also includes utilities for file uploads and deletions.
    *   **`LightGenerationHelper`**: This is the core engine for code generation. It processes blueprint definitions to create:
        *   Eloquent Models (with auto-generated `fillable`, `searchable`, `selectable`, `hidden` properties, and relationship methods).
        *   Database Migrations.
        *   API Controllers (with built-in validation and standardized response handling).
        *   Database Seeders.
        *   **Automatic Postman Collection Generation**: A notable feature is its ability to generate a Postman collection JSON file for the newly created API endpoints, providing instant API documentation and testing capabilities.
    *   **`LightModelHelper`**: Provides powerful query scopes for Eloquent models, enabling:
        *   **Dynamic Searching**: Case-insensitive search across multiple model attributes and relations using the `ILIKE` operator.
        *   **Flexible Filtering**: Advanced filtering capabilities with various operators (e.g., `eq`, `ne`, `in`, `ni`, `bw`) for model attributes and relations.
        *   **Selectable Columns**: Allows clients to specify which columns to retrieve, optimizing API responses.

3.  **Standardized API Response Structure**:
    The `LightControllerHelper` enforces a consistent JSON response format across all API endpoints, making client-side integration more predictable and robust.

4.  **PostgreSQL Preference**:
    The use of the `ILIKE` operator in the `LightModelHelper` for search functionality indicates a strong preference for, and likely an expectation of, a PostgreSQL database backend. While Laravel supports multiple databases, this specific implementation detail points towards PostgreSQL as the primary target.

5.  **Standard Laravel Configuration**:
    Unlike some highly customized projects, this repository **does not introduce custom configuration files** in the `config` directory. It adheres to Laravel's standard configuration structure, relying on environment variables (via the `.env` file) for application-specific settings.

In summary, "Yudha Queue - Laravel Light API Starter Kit" is a highly opinionated Laravel project designed to streamline API development through extensive code generation, standardized helper utilities, and a focus on rapid prototyping and consistent API design, particularly with a PostgreSQL backend in mind.