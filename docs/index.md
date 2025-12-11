# CodeIgniter Module Manager

A module is a self-contained package that adds specific functionality to your application. CodeIgniter 4 includes native support for modules, making it easy to organize features into isolated, reusable components.

This package provides a structured way to install, manage, and maintain the entire lifecycle of your modules, ensuring they can be added, updated, or removed cleanly and consistently.

### Requirements

![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-%5E4.6-blue)

### The Module Lifecycle

Modules move through distinct states during their lifetime:

- **Scanning** discovers modules in your directory and registers their metadata with the system.

- **Installation** creates the database schema by running migrations. The module is prepared but not yet active.

- **Enabling** makes the module fully functional. Its routes become available, its namespace is autoloaded, and it participates in handling requests.

- **Updating** applies incremental migrations or other update tasks.

- **Disabling** deactivates functionality but preserves all data in the database.

- **Uninstalling** performs a complete cleanup by rolling back migrations and removing database tables.

This separation between installation (database setup) and enabling (activation) gives you fine-grained control and matches patterns used in other systems like Prestashop, Wordpress, Drupal and Magento.
