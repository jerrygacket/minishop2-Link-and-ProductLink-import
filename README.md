# minishop2-Link-and-ProductLink-import

Импорт наборов (Связей и Товаров в связях) в miniShop2. Существующие связи обновляются, связи товаров создаются заново. Надо сделать, чтобы тоже обновлялись.

Скрипты для ипорта должны лежать в папке MODX_CORE_PATH/components/minishop2/import/.

В скрипте run.sh необходимо указать значения переменных remoteuser and remotehost.

Структура csv файлов для импорта:

- links.csv

    - названия колонок: имя связи,тип связи,класс объекта,описание

    - пример данных: link1;many-to-many;msLink;Набор одинаковых продуктов

- productlink.csv

    - названия колонок: id связи,артикулы товаров в связи

    - пример данных: 0;article1,article2,article3,article4

---

Importing Links and ProductLinks from csv, like a product import.

Links updated, if exists. ProductLinks removed, then created. ProductLinks must be updated too, but there is no need to do right now. Maybe later.

Import scripts must be in dir: MODX_CORE_PATH/components/minishop2/import/

Do not forget to set remoteuser and remotehost vars in run.sh.

Structure of csv files:

- links.csv

    - headers: link name,link type,class_key,description

    - data: link1;many-to-many;msLink;Link Description

- productlink.csv

    - headers: link id,goods by article

    - data: 0;article1,article2,article3,article4
