Soliant Consulting Apigility
============================

This tool can create an Apigility-enabled module with the Doctrine entities in scope.
To enable the Admin mode include ```'SoliantConsulting\Apigility',``` in your 
development.config.php configuration.

Before you begin configure your application with Doctrine entites.  All entities 
managed by the object manager will be available to build into a resource.  See
README.ENTITY-DESIGN.md for details on how to build your entities.

Browse to /soliant-consulting/apigility/admin to begin.  On this page you will enter 
the name of a new module which does not already exist.  When the form is submitted
the module will be created.

The next page allows you to select entities from the object manager to build into 
resources.  Check those you want then submit the form.

Done.  Your new module is enabled in your application and you can start making API 
requests.  

The route for an entity named UserData is
/api/userData

