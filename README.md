query-constructor
=================

Набор инструментов по созданию экземпляра Doctrine QueryBuilder через графический интерфейс с возможностью его сериализации/десериализации.

Входят следующие средства:

* Creator
* MetaDataProvider
* Serializer
* client

Набор инструментов
-------------

### Creator

Создаёт экземпляр Doctrine QueryBuilder из JSON

#### Формат JSON (пример)

```json
{
    "aggregateFunction": "COUNT", // required: COUNT|SUM|MIN|MAX|AVG
    "entity": "MyClass1", // required
    "property": "id" // required
    "conditions": [
        {
            "type": "NONE", // required: NONE|AND|OR
            "entity": "MyClass2",
            "property": "name", // required
            "operator": "=", // required
            "value": "John" // required
        }
    ]
}
```

Допустимые `entity`, `property` определяются из зарегеистрированных провайдеров (см. `MetaDataProvider`)

### MetaDataProvider

`ProviderRegistry` - реестр провайдеров, реализующих `ProviderInterface`

### Serializer

Сериализует свойства экземпляра Doctrine QueryBuilder в виде массива. Обратно десериализует массив в QueryBuilder.
Ключи массива:

* dqlParts,
* parameters,
* firstResult,
* maxResults,
* lifetime,
* cacheMode,
* cacheable,
* cacheRegion

Для успешного записи результата сериализации в БД может потребоваться использовать `addslashes()` для экранирования 0-символов.

### Client

React-компонент конструктора запросов

#### Установка
1. Выполнить `npm install` в корневой папке пакета query-constructor
2. Добавить в конфигурацию загрузчика babel путь к query-constructor/client
3. Создать symlink на query-constructor/client в папке с исходниками React основного проекта
4. В компоненте проекта импортировать компонент `QueryConstructor` из index.js

#### Настройка QueryConstructor
Требуются следующие параметры:

* **aggregateFunctions** - *required* {'имяКласса': 'Название'} - заполняет список аггрегирующих функций для выборки
* **entities** - *required* {'имяКласса': 'Название'} - заполняет список сущностей для выборки
* **propertiesUrl** - *required* - адрес ресурса, возвращающий свойства сущности в формате MetaDataProvider\ProviderRegistry->get()
* **prefix** - строка, добавляемая к имени всех элементов input, создаваемых компонентом

Конструктор формирует JSON, готовый для отдачи в Creator - в `input` с именем `prefix[sqlConstructor]`

Подключение к проекту (на примере Symfony 2/3)
----------------------------------------------

### Регистрация сервисов
```yml
services:
    query_constructor.creator:
        class: Informika\QueryConstructor\Creator\Creator
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@query_constructor.metadata_registry'
            - '@query_constructor.joiner'

    query_constructor.joiner:
        class: Informika\QueryConstructor\Creator\Joiner
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@query_constructor.metadata_registry'

    query_constructor.serializer:
        class: Informika\QueryConstructor\Serializer\Serializer
        arguments: ['@doctrine.orm.entity_manager']

    query_constructor.metadata_registry:
        class: Informika\QueryConstructor\MetaDataProvider\ProviderRegistry
```

### Регистрация провайдеров

```php
<?php

namespace QueryConstructorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class MetaDataRegistryLoader implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('query_constructor.metadata_registry')) {var_dump('gg');exit;
            return;
        }

        $registryDefinition = $container->findDefinition('query_constructor.metadata_registry');

        $providers = $container->findTaggedServiceIds('query_constructor.metadata_provider');

        foreach ($providers as $id => $tags) {
            $registryDefinition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
```
Теперь все сервисы, реализующие интерфейс `MetaDataRegistry\ProviderInterface` и помеченные тегом `query_constructor.metadata_provider`, будут добавлены в реестр провайдеров.

### Простейший контроллер для обслуживания React-компонента
```php
<?php

namespace QueryConstructorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/entities/", name="query_constructor.entities")
     *
     * @return JsonResponse
     */
    public function entitiesAction(): JsonResponse
    {
        return new JsonResponse([
            'result' => 'success',
            'entities' => $this->get('query_constructor.metadata_registry')->getRegisteredEntities(),
        ]);
    }

    /**
     * @Route("/properties/", name="query_constructor.properties")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function propertiesAction(Request $request): JsonResponse
    {
        try {
            $properties = $this->get('query_constructor.metadata_registry')->get($request->get('entity'));
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'result' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonResponse([
            'result' => 'success',
            'properties' => $properties,
        ]);
    }
}
```

Использование (на примере Symfony 2/3)
--------------------------------------

### Подключение React-компонента
```javascript
import QueryConstructor from '../queryConstructor/index'
...
<QueryConstructor prefix="myform[field]" {...this.props.queryConstructorProps} />
...
```

### Провайдер
```php
<?php

namespace AppBundle\Service\Report;

use AppBundle\Entity\Monitoring\AfterSchoolGroup;
use AppBundle\Entity\Monitoring\School;
use AppBundle\Entity\Monitoring\SchoolClass;
use AppBundle\Entity\Monitoring\Pupil;
use Doctrine\ORM\QueryBuilder;
use Informika\QueryConstructor\Creator\Joiner as QueryJoiner;
use Informika\QueryConstructor\MetaDataProvider\ProviderInterface as MetaDataProviderInterface;

/**
 * Class PupilMetaDataProvider
 */
class PupilMetaDataProvider implements MetaDataProviderInterface
{
    /**
     * @var QueryJoiner
     */
    protected $joiner;

    /**
     * Этот провайдер использует Joiner для добавления к запросу дополнительных условий
     *
     * @param QueryJoiner $joiner
     */
    public function __construct(QueryJoiner $joiner)
    {
        $this->joiner = $joiner;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregatableProperties(): array
    {
        return [
            'id' => 'ID',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return [
            'trainingProgram' => [
                'title' => 'Программа обучения',
                'type' => MetaDataProviderInterface::TYPE_MULTIPLE_CHOICE,
                'choices' => (object) Pupil::getTrainingProgramTitles(),
            ],
            'gender' => [
                'title' => 'Пол',
                'type' => MetaDataProviderInterface::TYPE_SINGLE_CHOICE,
                'choices' => (object) Pupil::getGenderTitles(),
            ],
            'birthDate' => [
                'title' => 'Дата рождения',
                'type' => MetaDataProviderInterface::TYPE_DATE,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinableEntities(): array
    {
        return [
            AfterSchoolGroup::class => 'afterSchoolGroupId',
            SchoolClass::class => 'schoolClassId',
            School::class => [
                SchoolClass::class,
                School::class,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityTitle(): string
    {
        return 'Ученик';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return Pupil::class;
    }

    /**
     * Задаёт дополнительные условия к построенному запросу
     *
     * @param QueryBuilder $qb
     * @param string $entitySelectAlias
     * @param \DateTime $dateReport
     */
    public function onQueryCreated(QueryBuilder $qb, string $entitySelectAlias, \DateTime $dateReport)
    {
        $this->addDateBetweenCondition($qb, $entitySelectAlias, $dateReport);
        $this->addSchoolCondition($qb, $entitySelectAlias, $dateReport);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entitySelectAlias
     * @param \DateTime $dateReport
     */
    protected function addDateBetweenCondition(QueryBuilder $qb, string $entitySelectAlias, \DateTime $dateReport)
    {
        $qb->andWhere(":reportDate BETWEEN {$entitySelectAlias}.fromDate AND {$entitySelectAlias}.toDate");
        $qb->setParameter(':reportDate', $dateReport, Type::DATETIME);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entitySelectAlias
     * @param string $entityClass
     * @param \DateTime $dateReport
     */
    protected function addSchoolCondition(QueryBuilder $qb, string $entityAlias, \DateTime $dateReport)
    {
        $classAlias = $this->joiner->join($qb, SchoolClass::class, $dateReport);
        $qb->andWhere($entityAlias . '.school = :schoolId'); // Можно объявить параметр, а задать его позже, например, после десериализации
    }
}

```

### Регистрация провайдера
```yml
services:
    app.metadata_constructor.provider_school:
        class: AppBundle\Service\Report\SchoolMetaDataProvider
        tags:
            - { name: query_constructor.metadata_provider }
```

### Получение QueryBuilder из запроса
```php
$queryBuilder = $this->get('query_constructor.creator')->createFromJson($formParams['sqlConstructor']));
```

### Сохранение QueryBuilder в БД
```php
$entity->setSqlFilter(addslashes($this->get('query_constructor.serializer')->serialize(queryBuilder)));
```

### Восстановление QueryBuilder из БД
```php
$queryBuilder = $this->get('query_constructor.serializer')->unserialize(stripslashes($$entity->getSqlFilter()));
```
