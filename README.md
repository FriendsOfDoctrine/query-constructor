query-constructor
=================

Набор инструментов по созданию экземпляра Doctrine QueryBuilder через графический интерфейс с возможностью его сериализации/десериализации.

Входят следующие инструменты:

* Bundle - бандл Symfony3, бэкенд конструктора
* client - фронтенд конструктора на React-Redux
* Creator - сервис по созданию экземплярыа QueryBuilder
* Serializer - сервис по сериализации-десериализации QueryBuilder

Требования
----------

1. PHP 5.4+
2. Doctrine/ORM 2.5+

Подключение к проекту (на примере Symfony 2/3)
----------------------------------------------

### Регистрация бандла QueryConstructorBundle
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

### Регистрация сущностей, загружаемых в конструктор

Все настройки выполняются через аннотации классов и свойств.

#### Минимальная настройка
```php
<?php

// ...

use Informika\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity()
 */
class Entity
{
    /**
     * @var int
     */
    protected $id;
}
```

Обязательно указать аннотацию класса `Entity`

Все свойства класса будут доступны как для выборки, так и для фильтрации. Тип определяется автоматически по Doctrine-аннотации свойства.

#### Расширенная настройка
```php
<?php

// ...

use Informika\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(title="Сущность", aggregatable_fields="id", filterable_fields_except={"prop1", "prop2"}, date_between={"fromDate", "toDate")
 */
class Entity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var \AppBundle\Entity\QuestionGroup
     *
     * @QC\Property(type="single_choice", title="Группа", list={"entity":"\AppBundle\Entity\QuestionGroup", "title":"title", "value":"id"})
     */
    protected $group;

    /**
     * @var MonitoringDateTime
     */
    protected $fromDate;

    /**
     * @var \DateTime
     */
    protected $toDate;
}
```

##### Необязательные опции аннотации класса Entity

* **title** *string* - название сущности в конструкторе (если не задано, выводится название класса)
* **aggregatable_fields** *string|array* - названия свойств сущности для списка выборки (если не задано, участвуют все свойства)
* **aggregatable_fields_except** *string|array* - исключить указанные свойства сущности из списка выборки
* **filterable_fields** *string|array* - названия свойств сущности для списка фильтрации (условий) (если не задано, участвуют все свойства)
* **filterable_fields_except** *string|array* - исключить указанные свойства сущности из списка фильтрации (условий)
* **date_between** *array* - названия двух колонок (например, `fromDate`, `toDate`), которые будут добавлены в виде условия ` AND (:dateReport BETWEEN fromDate AND toDate)`, где `:dateReport` - дата, на которую требуется получить отчет.

##### Необязательные опции аннотации свойства Property

* **title** *string* - название свойства в конструкторе (если не задано, выводится исходное название свойства)
* **type** *string* - тип свойства (если не задано, определяется по phpdoc). Поддерживаются значения `integer`, `string`, `date`, `single_choice`, `multiple_choice`.
* **list** *array* - параметры загрузки списка выбора. Ключи: `entity` - класс сущности-справочника, `value` - свойство-значение, `title` - свойство-подпись.

##### Указание условий из связанных сущностей

Указать тип phpdoc свойства как класс, в котором задана аннотация `Entity`. Такой класс будет сразу доступен в конструкторе запроса в списке условий.

##### Определение типа свойства по phpdoc (если type не указан явно)
Тип свойства - тип phpdoc:
* integer - int, integer, bool, boolean
* date - DateTime, \DateTime
* multiple_choice - phpdoc не учитывается, если задана опция аннотации свойства list
* string - все остальные случаи

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

### Bundle

Бэкенд конструктора запросов, регистрация сервисов для Symfony

#### Роуты прилагаемого контроллера для конструктора
* informika.query_constructor.entities - список сущностей для выборки
* informika.query_constructor.properties - информация по выбранной сущности (свойства для выборки, фильтров, возможные связи)

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
* **propertiesUrl** - *required* - адрес ресурса, возвращающий свойства сущности в формате Metadata\Registry->get()
* **prefix** - строка, добавляемая к имени всех элементов input, создаваемых компонентом

Конструктор формирует JSON, готовый для отдачи в Creator - в `input` с именем `prefix[sqlConstructor]`
