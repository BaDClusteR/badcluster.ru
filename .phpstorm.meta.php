<?php

namespace PHPSTORM_META
{
    override(\Runway\Singleton\Container::getService(0), map([
        // vendor/bad_cluster/runway/config/config.yaml
        'Runway\Event\IEventDispatcher' => \Runway\Event\EventDispatcher::class,
        'Runway\Dumper\IDumper' => \Runway\Dumper\Dumper::class,
        'Runway\FileSystem\IFileSystem' => \Runway\FileSystem\FileSystem::class,
        'Runway\Logger\ILogger' => \Runway\Logger\Logger::class,

        // vendor/bad_cluster/runway/config/services/models.yaml
        'Runway\Model\Provider\IDataStoragePropertiesProvider' => \Runway\Model\Provider\DataStoragePropertiesProvider::class,
        'Runway\Model\Helper\IDataStoragePropertiesHelper' => \Runway\Model\Helper\DataStoragePropertiesHelper::class,
        'Runway\Model\Converter\IDataStoragePropertyBuilder' => \Runway\Model\Converter\DataStoragePropertyBuilder::class,
        'Runway\Model\Converter\IDataStoragePropertiesConverter' => \Runway\Model\Converter\DataStoragePropertiesConverter::class,
        'Runway\Model\DataBuilder\IDataStoragePropertyBuilder' => \Runway\Model\DataBuilder\DataStoragePropertyBuilder::class,

        // vendor/bad_cluster/runway/config/services/request.yaml
        'Runway\Request\IRequest' => \Runway\Request\Request::class,
        'Runway\Request\IRequestRead' => \Runway\Request\Request::class,
        'Runway\Request\IResponse' => \Runway\Request\Response::class,
        'Runway\IRequestParameterValue' => \Runway\RequestParameterValue::class,
        'Runway\Request\Parameters\DataBuilder\IFileParametersDataBuilder' => \Runway\Request\Parameters\DataBuilder\FileParametersDataBuilder::class,
        'Runway\Request\Parameters\Provider\IRequestParametersProvider' => \Runway\Request\Parameters\Provider\RequestParametersProvider::class,

        // vendor/bad_cluster/runway/config/services/data-storage.yaml
        'Runway\DataStorage\IDataStorageDriver' => \Runway\DataStorage\PDO::class,
        'Runway\DataStorage\QueryBuilder\Converter\IComparisonOperatorConverter' => \Runway\DataStorage\QueryBuilder\Converter\ComparisonOperatorConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\IJoinTypeConverter' => \Runway\DataStorage\QueryBuilder\Converter\JoinTypeConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\IJoinConditionTypeConverter' => \Runway\DataStorage\QueryBuilder\Converter\JoinConditionTypeConverter::class,
        'Runway\DataStorage\QueryBuilder\IQueryBuilder' => \Runway\DataStorage\QueryBuilder\QueryBuilder::class,
        'Runway\DataStorage\QueryBuilder\ExpressionBuilder\IExpressionBuilder' => \Runway\DataStorage\QueryBuilder\ExpressionBuilder\ExpressionBuilder::class,
        'Runway\DataStorage\QueryBuilder\Converter\IQueryOperationConverter' => \Runway\DataStorage\QueryBuilder\Converter\QueryOperationConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\ITableNameEscaper' => \Runway\DataStorage\QueryBuilder\Converter\TableNameEscaper::class,

        // vendor/bad_cluster/runway/config/services/singletons.yaml
        'Runway\Singleton\IConverter' => \Runway\Singleton\Converter::class,

        // vendor/bad_cluster/runway/config/services/module.yaml
        'Runway\Module\Parser\IModuleConfigParser' => \Runway\Module\Parser\ModuleConfigParser::class,
        'Runway\Module\Provider\IModuleProvider' => \Runway\Module\Provider\ModuleProvider::class,

        // vendor/bad_cluster/runway/config/services/core.yaml
        'Runway\Env\Provider\IEnvVariablesProvider' => \Runway\Env\Provider\EnvVariablesProvider::class,
        'Runway\Singleton\IKernel' => \Runway\Singleton\Kernel::class,
        'Runway\ISingleton' => \Runway\Singleton::class,
        'Runway\Service\Provider\IConfigProvider' => \Runway\Service\Provider\ConfigProvider::class,
        'Runway\Module\IModuleProvider' => \Runway\Module\ModuleProvider::class,
        'Runway\Service\Provider\IPathsProvider' => \Runway\Service\Provider\PathsProvider::class,

        // vendor/bad_cluster/runway/config/services/router.yaml
        'Runway\Router\IRouter' => \Runway\Router\Router::class,
        'Runway\Controller\IController404' => \Runway\Controller\Controller404::class,
        'Runway\Controller\IErrorController' => \Runway\Controller\ErrorController::class,
        'Runway\Controller\IExceptionController' => \Runway\Controller\ExceptionController::class,

        // config/services.yaml
        'BC\Core\Scanner\IWidgetClassScanner' => \BC\Core\Scanner\WidgetClassScanner::class,
        'BC\Core\Asset\Minifier\IMinifier' => \BC\Core\Asset\Minifier\MinifierFactory::class,
        'BC\Core\Asset\IAssetBuilder' => \BC\Core\Asset\AssetBuilder::class,
        'BC\Core\Config\IWebsiteSettings' => \BC\Core\Config\WebsiteSettings::class,
        'BC\Core\Media\IThumbnailGenerator' => \BC\Core\Media\ThumbnailGenerator::class,
        'BC\Provider\IMenuItemsProvider' => \BC\Provider\MenuItemsProvider::class,
        'BC\Provider\IPulseItemsProvider' => \BC\Provider\PulseItemsProvider::class,
        'BC\Provider\IPathsProvider' => \BC\Provider\PathsProvider::class,
        'BC\Generator\IThumbnailsGenerator' => \BC\Generator\ThumbnailsGenerator::class,
        'BC\Controller\Index' => \BC\Controller\Index::class,

        // vendor/bad_cluster/runway-console-app/config/services.yaml
        'Runway\Console\IApplication' => \Runway\Console\Application::class,
        'Runway\Console\Input\Parser\IInputParser' => \Runway\Console\Input\Parser\InputParser::class,
        'Runway\Console\Output\IOutput' => \Runway\Console\Output\Output::class,
        'Runway\Console\Output\Formatter\IOutputFormatter' => \Runway\Console\Output\Formatter\OutputFormatter::class,
        'Runway\Console\Output\Table\ITable' => \Runway\Console\Output\Table\Table::class,
    ]));

    override(\Runway\Singleton\Container::tryGetService(0), map([
        // vendor/bad_cluster/runway/config/config.yaml
        'Runway\Event\IEventDispatcher' => \Runway\Event\EventDispatcher::class,
        'Runway\Dumper\IDumper' => \Runway\Dumper\Dumper::class,
        'Runway\FileSystem\IFileSystem' => \Runway\FileSystem\FileSystem::class,
        'Runway\Logger\ILogger' => \Runway\Logger\Logger::class,

        // vendor/bad_cluster/runway/config/services/models.yaml
        'Runway\Model\Provider\IDataStoragePropertiesProvider' => \Runway\Model\Provider\DataStoragePropertiesProvider::class,
        'Runway\Model\Helper\IDataStoragePropertiesHelper' => \Runway\Model\Helper\DataStoragePropertiesHelper::class,
        'Runway\Model\Converter\IDataStoragePropertyBuilder' => \Runway\Model\Converter\DataStoragePropertyBuilder::class,
        'Runway\Model\Converter\IDataStoragePropertiesConverter' => \Runway\Model\Converter\DataStoragePropertiesConverter::class,
        'Runway\Model\DataBuilder\IDataStoragePropertyBuilder' => \Runway\Model\DataBuilder\DataStoragePropertyBuilder::class,

        // vendor/bad_cluster/runway/config/services/request.yaml
        'Runway\Request\IRequest' => \Runway\Request\Request::class,
        'Runway\Request\IRequestRead' => \Runway\Request\Request::class,
        'Runway\Request\IResponse' => \Runway\Request\Response::class,
        'Runway\IRequestParameterValue' => \Runway\RequestParameterValue::class,
        'Runway\Request\Parameters\DataBuilder\IFileParametersDataBuilder' => \Runway\Request\Parameters\DataBuilder\FileParametersDataBuilder::class,
        'Runway\Request\Parameters\Provider\IRequestParametersProvider' => \Runway\Request\Parameters\Provider\RequestParametersProvider::class,

        // vendor/bad_cluster/runway/config/services/data-storage.yaml
        'Runway\DataStorage\IDataStorageDriver' => \Runway\DataStorage\PDO::class,
        'Runway\DataStorage\QueryBuilder\Converter\IComparisonOperatorConverter' => \Runway\DataStorage\QueryBuilder\Converter\ComparisonOperatorConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\IJoinTypeConverter' => \Runway\DataStorage\QueryBuilder\Converter\JoinTypeConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\IJoinConditionTypeConverter' => \Runway\DataStorage\QueryBuilder\Converter\JoinConditionTypeConverter::class,
        'Runway\DataStorage\QueryBuilder\IQueryBuilder' => \Runway\DataStorage\QueryBuilder\QueryBuilder::class,
        'Runway\DataStorage\QueryBuilder\ExpressionBuilder\IExpressionBuilder' => \Runway\DataStorage\QueryBuilder\ExpressionBuilder\ExpressionBuilder::class,
        'Runway\DataStorage\QueryBuilder\Converter\IQueryOperationConverter' => \Runway\DataStorage\QueryBuilder\Converter\QueryOperationConverter::class,
        'Runway\DataStorage\QueryBuilder\Converter\ITableNameEscaper' => \Runway\DataStorage\QueryBuilder\Converter\TableNameEscaper::class,

        // vendor/bad_cluster/runway/config/services/singletons.yaml
        'Runway\Singleton\IConverter' => \Runway\Singleton\Converter::class,

        // vendor/bad_cluster/runway/config/services/module.yaml
        'Runway\Module\Parser\IModuleConfigParser' => \Runway\Module\Parser\ModuleConfigParser::class,
        'Runway\Module\Provider\IModuleProvider' => \Runway\Module\Provider\ModuleProvider::class,

        // vendor/bad_cluster/runway/config/services/core.yaml
        'Runway\Env\Provider\IEnvVariablesProvider' => \Runway\Env\Provider\EnvVariablesProvider::class,
        'Runway\Singleton\IKernel' => \Runway\Singleton\Kernel::class,
        'Runway\ISingleton' => \Runway\Singleton::class,
        'Runway\Service\Provider\IConfigProvider' => \Runway\Service\Provider\ConfigProvider::class,
        'Runway\Module\IModuleProvider' => \Runway\Module\ModuleProvider::class,
        'Runway\Service\Provider\IPathsProvider' => \Runway\Service\Provider\PathsProvider::class,

        // vendor/bad_cluster/runway/config/services/router.yaml
        'Runway\Router\IRouter' => \Runway\Router\Router::class,
        'Runway\Controller\IController404' => \Runway\Controller\Controller404::class,
        'Runway\Controller\IErrorController' => \Runway\Controller\ErrorController::class,
        'Runway\Controller\IExceptionController' => \Runway\Controller\ExceptionController::class,

        // config/services.yaml
        'BC\Core\Scanner\IWidgetClassScanner' => \BC\Core\Scanner\WidgetClassScanner::class,
        'BC\Core\Asset\Minifier\IMinifier' => \BC\Core\Asset\Minifier\MinifierFactory::class,
        'BC\Core\Asset\IAssetBuilder' => \BC\Core\Asset\AssetBuilder::class,
        'BC\Core\Config\IWebsiteSettings' => \BC\Core\Config\WebsiteSettings::class,
        'BC\Core\Media\IThumbnailGenerator' => \BC\Core\Media\ThumbnailGenerator::class,
        'BC\Provider\IMenuItemsProvider' => \BC\Provider\MenuItemsProvider::class,
        'BC\Provider\IPulseItemsProvider' => \BC\Provider\PulseItemsProvider::class,
        'BC\Provider\IPathsProvider' => \BC\Provider\PathsProvider::class,
        'BC\Generator\IThumbnailsGenerator' => \BC\Generator\ThumbnailsGenerator::class,
        'BC\Controller\Index' => \BC\Controller\Index::class,

        // vendor/bad_cluster/runway-console-app/config/services.yaml
        'Runway\Console\IApplication' => \Runway\Console\Application::class,
        'Runway\Console\Input\Parser\IInputParser' => \Runway\Console\Input\Parser\InputParser::class,
        'Runway\Console\Output\IOutput' => \Runway\Console\Output\Output::class,
        'Runway\Console\Output\Formatter\IOutputFormatter' => \Runway\Console\Output\Formatter\OutputFormatter::class,
        'Runway\Console\Output\Table\ITable' => \Runway\Console\Output\Table\Table::class,
    ]));
}
