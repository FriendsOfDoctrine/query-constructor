query-constructor
=================

Набор инструментов по созданию экземпляра Doctrine QueryBuilder через графический интерфейс с возможностью его сериализации/десериализации.

Входят следующие инструменты:

* Creator
* Registry
* Serializer
* 
* client

Требования
----------

1. PHP 5.4+
2. Doctrine/ORM 2.5+

Установка
-------------

### 



Подключение к проекту (на примере Symfony 2/3)
----------------------------------------------

### Регистрация прилагаемого бандла
```php
// /your-project/app/AppKernel.php
// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Informika\QueryConstructor\Bundle\QueryConstructorBundle(),
        ];
        // ...
    }
// ...
}
```

### Настройка бандла
```yml
# /your-project/app/config/config.yml
# ...

# Map entity namespaces & paths relative to /your-project/app/ for discovery service
query_constructor:
    discovery:
        - ['AppBundle\Entity', '/../src/AppBundle/Entity']
#...
```

### Регистрация сущностей, загружаемых в конструктор

Минимальная настройка
```php
<?php

// ...

use Informika\QueryConstructor\Mapping\Annotation as OLAP;

/**
 * @OLAP\Entity(title="Сущность")
 */
class Entity
{
    /**
     * @var int
     */
    protected $id;
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

### Получение QueryBuilder из запроса
```php
$queryBuilder = $this->get('query_constructor.creator')->createFromJson($formParams['sqlConstructor']));
```

### Сохранение QueryBuilder в БД
```php
$entity->setSqlFilter(addslashes($this->get('query_constructor.serializer')->serialize($queryBuilder)));
```

### Восстановление QueryBuilder из БД
Например, сериализованный QueryBuilder возвращается `$entity->getSqlFilter()`:
```php
$queryBuilder = $this->get('query_constructor.serializer')->unserialize(stripslashes($entity->getSqlFilter()));
```

Подробнее об инструментах пакета
-------------

### Creator

Создаёт экземпляр Doctrine QueryBuilder из JSON

#### Формат JSON

```json
{
    "aggregateFunction": "COUNT",
    "entity": "MyClass1",
    "property": "id"
    "conditions": [
        {
            "type": "NONE",
            "entity": "MyClass2",
            "property": "name",
            "operator": "=",
            "value": "John"
        }
    ]
}
```

Массив `conditions[]` может быть пустым.
Все поля, кроме `conditions[].entity` - обязательные.
Допустимые значения `aggregateFunction` - `COUNT`, `SUM`, `MIN`, `MAX`, `AVG`.
Допустимые значения `conditions[].type` - `NONE`, `AND`, `OR`.
Допустимые `entity`, `property` определяются из зарегеистрированных провайдеров (см. `MetaDataProvider`).

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
5. Пересобрать js с новым компонентом сборщиком, используемым на проекте

#### Настройка QueryConstructor
Требуются следующие параметры:

* **aggregateFunctions** - *required* {'имяКласса': 'Название'} - заполняет список аггрегирующих функций для выборки
* **entities** - *required* {'имяКласса': 'Название'} - заполняет список сущностей для выборки
* **propertiesUrl** - *required* - адрес ресурса, возвращающий свойства сущности в формате MetaDataProvider\ProviderRegistry->get()
* **prefix** - строка, добавляемая к имени всех элементов input, создаваемых компонентом

Конструктор формирует JSON, готовый для отдачи в Creator - в `input` с именем `prefix[sqlConstructor]`
