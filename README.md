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

Doctrine/ORM 2.5+

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
            new FOD\QueryConstructor\Bundle\QueryConstructorBundle(),
        ];
        // ...
    }
// ...
}
```

### Рендер формы конструктора

В папке `/assets` пакета содержатся готовые скомпилированные js-файлы.

#### Минимальная настройка

1. Создать симлинк на папку с js-файлами из публичной папки с js (путь по умолчанию: `/web/assets/js`)

```
cd /my-project/web/assets/js
ln -sfn /my-project/vendor/FriendsOfDoctrine/query-constructor/assets query-constructor
```

2. Отрисовать форму конструктора через шаблонизатор twig

```twig
{{ fod_query_constructor()|raw }}
```

#### Указание собственного пути к js-файлу

Передать опцию `scriptPath` со ссылкой на js-файл, который будет использован вместо пути по умолчанию `/web/assets/js`

```twig
{{ fod_query_constructor({'scriptPath' : '/path/to/myfile.js'})|raw }}
```

#### Указание HTML-атрибута id корневого элемента конструктора

Передать опцию `htmlId` со строковым значением, которое будет использовано вместо id по умолчанию `fod-query-constructor`

```twig
{{ fod_query_constructor({'htmlId' : 'custom-constructor-id'})|raw }}
```

#### Указание префикса к генерируемым элементам формы

Передать опцию `prefix` со строковым значением, которое будет использовано в качестве префикса к элементам формы

```twig
{{ fod_query_constructor({'prefix' : 'form[query]'})|raw }}
```

В результате атрибут `name` элемента `input` получит значение `form[query][entity]` вместо `entity`

### Наполнение конструктора сущностями

Все настройки выполняются через аннотации классов и свойств.

#### Выбор сущностей, попадающих в конструктор

**Что сделать**

Указать аннотацию класса `QC\Entity`.

**Результат**

1. Сущность попадет в конструктор. Название в списке выбора - имя класса.
2. Можно аггрегировать и фильтровать по всем свойствам класса, которые отмечены аннотацией `ORM\Column`.
3. Свойства, отмеченные аннотацией `ORM\ManyToOne`, становятся фильтрами со списками выбора (можно выбрать несколько значений)

**Пример**

В конструктор должна попасть сущность `Room` с фильтрами `Id` (integer), `Name` (string) и `Building` (список выбора)

```php
<?php

// ...

use Doctrine\ORM\Mapping as ORM;
use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity()
 */
class Room
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     */
    protected $building;
}
```

#### Добавление подписи сущности в конструкторе

**Что сделать**

Добавить опцию `title` аннотации класса `QC\Entity`.

**Результат**

Сущность будет назваться, как указано в `title`. Если опция не задана, используется название свойства в классе.

**Пример**

Сущность Room должна иметь подпись `Помещение`

```php
<?php

// ...

use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(title="Помещение")
 */
class Room
{
    // ...
}
```

#### Добавление подписи свойства в конструкторе

**Что сделать**

Добавить аннотацию свойства `QC\Property` с опцией `title`.

**Результат**

Свойство будет назваться, как указано в `title`. Если опция не задана, используется название класса.

**Пример**

Фильтр `Building` должен иметь подпись `Здание`

```php
<?php

// ...

use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(title="Помещение")
 */
class Room
{
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     * @QC\Property(title="Здание")
     */
    protected $building;

}
```

#### Указание поля с подписью для значений фильтра-выпадающего списка

**Что сделать**

Добавить опцию `titleField` в аннотацию свойства `QC\Property` с указанием названия свойства связанной сущности, откуда будут взяты значения подписей.

**Результат**

Подписи элементов списка будут взяты из указанного свойства связанной сущности. Если опция `titleField` не задана, подписи берутся из первого поля типа string. Если в сущности такого поля нет и опция не задана явно, выбрасывается исключение.

**Пример**

Вывести подписи из поля `Building.address`

```php
<?php

// ...

use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(title="Помещение")
 */
class Room
{
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     * @QC\Property(title="Здание", titleField="address")
     */
    protected $building;

}
```

#### Определение свойств для аггрегации, попадающих в конструктор

**Что сделать**

Добавить опцию `aggregatable_fields` в аннотацию класса `QC\Entity` с указанием названий свойств, к которым применять аггрегирующие функции. Можно задавать как массив, так и строку (если одно поле).

**Результат**

В списке аггрегации будут присутствовать только указанные свойства. Если не задана, присутствует только первичный ключ

**Пример**

В список аггрегации должны попасть `Id` и `Name`

```php
<?php

// ...

use Doctrine\ORM\Mapping as ORM;
use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(aggregatable_fields={"id", "name"})
 */
class Room
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     */
    protected $building;
}
```

#### Определение свойств для фильтрации, попадающих в конструктор

**Что сделать**

Добавить опцию `filterable_fields` в аннотацию класса `QC\Entity` с указанием названий свойств, по которым можно фильтровать. Можно задавать как массив, так и строку (если одно поле).

**Результат**

В списках фильтров будут присутствовать только указанные свойства. Если опция не задана, присутствуют все свойства.

**Пример**

В список фильтрации должен попасть только `Name`

```php
<?php

// ...

use Doctrine\ORM\Mapping as ORM;
use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(filterable_fields="name")
 */
class Room
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     */
    protected $building;
}
```

#### Исключение свойств для фильтрации, попадающих в конструктор

**Что сделать**

Добавить опцию `filterable_fields_except` в аннотацию класса `QC\Entity` с указанием названий свойств, которые исключить из фильтрации. Можно задавать как массив, так и строку (если одно поле).

**Результат**

В списках фильтров указанные свойства будут отсутствовать. Если опция применяется совместно с `filterable_fields`, будут выведены только `filterable_fields` за исключением свойств, отмеченных в `filterable_fields_except`.

**Пример**

В список фильтрации не должен попасть `Id`

```php
<?php

// ...

use Doctrine\ORM\Mapping as ORM;
use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(filterable_fields_except="id")
 */
class Room
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Building", inversedBy="rooms")
     */
    protected $building;
}
```

#### Добавление условия по дате для выборки сущности

**Что сделать**

Добавить опцию `date_between` аннотации класса `QC\Entity`. В опции указываются названия двух свойств сущности, содержащие даты "от" и "до".

**Результат**

К построенному запросу будет добавлено условие ` AND (:dateReport BETWEEN column1 AND column2)`, где `:dateReport` - дата, которая приходит из формы (удобно, если в таблице сущности применен паттерн "Версия реализации").

**Пример**

К запросу по сущности `Room` будет добавлено условие ` AND (:dateReport BETWEEN fromDate AND toDate)` с параметром `dateReport`.

```php
<?php

// ...

use FOD\QueryConstructor\Mapping\Annotation as QC;

/**
 * @QC\Entity(date_between={"fromDate", "toDate"})
 */
class Room
{
    // ...

    /**
     * @ORM\Column(type="datetime")
     */
    protected $fromDate;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $toDate;
}
```

#### Указание связанных сущностей для фильтров

**Что сделать**

Указать аннотацию класса `QC\Entity` в связанной сущности (которая отмечена в текущей сущности аннотацией `ORM\ManyToOne`.

**Результат**

При задании фильтра можно будет выбрать связанную сущность и ее свойства, настраиваемые по правилам, указанным выше.


Использование (на примере Symfony 2/3)
--------------------------------------

### Рендер формы конструктора через шаблонизатор Twig

```twig
{{ fod_query_constructor()|raw }}
```

Параметры запроса записываются в скрытый input с именем `sqlConstructor` (к имени можно добавить префикс - см. настройку рендера выше)

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
* fod.query_constructor.index - начальные данные для конструктора
* fod.query_constructor.properties - информация по выбранной сущности (свойства для выборки, фильтров, возможные связи)

### Client

Фронтенд-часть конструктора.

Альтернативы использования:

1. Скомпилированные файлы - расположены в папке `/assets` проекта (настройка файлов см. выше)
2. Подключение React-компонента к приложению проекта (см.ниже)

####React-компонент конструктора запросов

##### Установка
1. Выполнить `npm install` в корневой папке пакета query-constructor
2. Добавить в конфигурацию загрузчика babel путь к query-constructor/client
3. Создать symlink на query-constructor/client в папке с исходниками React основного проекта
4. В компоненте проекта импортировать компонент `QueryConstructor` из QueryConstructor.js
5. Пересобрать js с новым компонентом сборщиком, используемым на проекте

##### Подключение React-компонента к проекту
```javascript
import QueryConstructor from '../queryConstructor/QueryConstructor'
...
<QueryConstructor prefix="myform[field]" {...this.props.queryConstructorProps} />
...
```

##### Настройка QueryConstructor
Требуются следующие параметры:

* **aggregateFunctions** - *required* {'имяКласса': 'Название'} - заполняет список аггрегирующих функций для выборки
* **entities** - *required* {'имяКласса': 'Название'} - заполняет список сущностей для выборки
* **propertiesUrl** - *required* - адрес ресурса, возвращающий свойства сущности в формате Metadata\Registry->get()
* **prefix** - строка, добавляемая к имени всех элементов input, создаваемых компонентом

Конструктор формирует JSON, готовый для отдачи в Creator - в `input` с именем `prefix[sqlConstructor]`
