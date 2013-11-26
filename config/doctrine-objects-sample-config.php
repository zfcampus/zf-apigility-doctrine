<?php
array(

    // Add abstract factories for hydrator and resources
    'service_manager' => array(
        'abstract_factories' => array(
            'SoliantConsulting\Apigility\Server\Resource\DoctrineResourceFactory',
            'SoliantConsulting\Apigility\Server\Hydrator\DoctrineHydratorFactory',
        ),
    ),

    // Configure doctrine hydrators
    // Note: doctrine_hydrator is optional and will fall back to the hydrator in de doctrine-module.
    'zf-rest-doctrine-hydrator' => array(
        'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator' => array(
            'entity_class' => 'FileSystem\\Entity\\Asset',
            'doctrine_hydrator' => '\\DoctrineMongoODMModule\\Hydrator\\FileSystemEntityAssetHydrator',
            'object_manager' => 'doctrine.documentmanager.odm_filesystem',
        ),
        'FileSystemApi\\V1\\Rest\\Directories\\DirectoriesHydrator' => array(
            'entity_class' => 'FileSystem\\Entity\\Directory',
            'doctrine_hydrator' => '\\DoctrineMongoODMModule\\Hydrator\\FileSystemEntityDirectoryHydrator',
            'object_manager' => 'doctrine.documentmanager.odm_filesystem',
        ),
    ),

    // Add doctrine hydrators to the hydrator plugin manager
    'hydrators' => array (
        'invokables' => array(
            'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator' => 'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator',
            'FileSystemApi\\V1\\Rest\\Directories\\DirectoriesHydrator' => 'FileSystemApi\\V1\\Rest\\Directories\\DirectoriesHydrator',
        )
    ),


    // Configure doctrine resources
    // Note: hydrator is optional and will fall back to the hydrator in de doctrine-module.
    'zf-rest-doctrine-resource' => array(
        'FileSystemApi\\V1\\Rest\\Assets\\AssetsResource' => array(
            'object_manager' => 'doctrine.documentmanager.odm_filesystem',
            'hydrator' => 'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator',
        ),
        'FileSystemApi\\V1\\Rest\\Directories\\DirectoriesResource' => array(
            'object_manager' => 'doctrine.documentmanager.odm_filesystem',
            'hydrator' => 'FileSystemApi\\V1\\Rest\\Assets\\DirectoriesHydrator',
        ),

    ),

    // Add the DoctrineObject hydrator the zf-hal configuration. This will only use the extract() method anyway.
    // When the default HydratorManager evolves, maybe it is possible to inject new hydrators in it.
    'zf-hal' => array(
        'metadata_map' => array(
            'FileSystem\\Entity\\Asset' => array(
                'hydrator' => 'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator',
            ),
            'FileSystem\\Entity\\Directory' => array(
                'hydrator' => 'FileSystemApi\\V1\\Rest\\Assets\\AssetsHydrator',
            ),
        ),
    ),
);