# Модуль оплаты 1C-Битрикс для АИС Расчет (ЕРИП)

## Установка модуля

  * Создайте резервную копию вашего магазина и базы данных
  * Скачайте архив модуля в кодировке UTF-8 [bitrix-devtm-erip.zip](https://github.com/beGateway/bitrix-erip-payment-module/raw/master/bitrix-devtm-erip.zip) или в Windows-1251 [bitrix-devtm-erip-windows-1251.zip](https://github.com/beGateway/bitrix-erip-payment-module/raw/master/bitrix-devtm-erip-windows-1251.zip)
  * Распакуйте архив и скопируйте каталог `devtm.erip` в каталог
  `<1C-Bitrix/bitrix/modules/`
  * Зайдите в зону 1C-Битрикс администратора и выберите меню
  `Marketplace -> Установленные решения`
  * Установите модуль. Будет создана платежная система с обработчиками.

## Настройка модуля

  * Зайдите в зону 1C-Битрикс администратора и выберите меню `Настройки -> Настройки продукта -> Модуль платёжной системы Расчёт (ЕРИП)`
  * Введите в полях _Домен API_, _ID магазина_, _Ключ магазина_ значения, полученные от вашей платежной компании
  * В поле _Сколько дней действителен счет в ЕРИП_ задайте сколько дней будет доступен к оплате заказ в системе Расчёт
  * Укажите в _Страница уведомления после оплаты_ адрес страницы для уведомления, где был размещен и настроен компонент `sale.order.payment.receive`. В параметрах компонента указать тип плательщика и созданную платежную систему
  * Введите в поле _Номер сервиса в системе ЕРИП_ присвоенный вашему магазину код услуги в системе ЕРИП
  * Введите в поле _Имя компании в системе ЕРИП_ имя вашей компании или ИП, зарегистрированной в ЕРИП
  * Введите в поле _Имя магазина в системе ЕРИП_ имя вашего магазина, зарегистрированного в ЕРИП
  * Введите в поле _Путь к услуге в дереве системы ЕРИП_ путь к вашей услуге в дереве ЕРИП
  * Введите в поле _Описание сервиса для плательщика_ подсказку плательщику при оплате (например, _Введите номер заказа_)
  * Введите в поле _Ответ покупателю_ информацию, которая будет напечатана на чеке-подверждении об оплате (например, _Спасибо за оплату_)
  * Отметьте поле _Автоматически создавать счета в ЕРИП_, чтобы счета для оплаты в ЕРИП создавались автоматически
  * Нажмите _Сохранить_

## Работа с заказами при выборе оплаты через АИС Расчёт (ЕРИП)

Данный алгоритм работы с заказом действителен, если поле _Автоматически создавать счета в ЕРИП_ не было отмечено и был настроен ручной режим работы с заказами через ЕРИП.

В случае получения заказа со способом оплаты через АИС Расчет (ЕРИП), менеджер
магазина, видя заказ и потдверждая его с клиентом, переводит его в
статус _[ЕРИП] Ожидание оплаты_.

В момент смены статуса заказа посылается запрос на сервер для регистрации счёта
на оплату в ЕРИП. В случае успешного ответа клиенту приходит письмо с
инструкцией как оплатить заказ через ЕРИП, а статус заказа
становится _[ЕРИП] Ожидание оплаты_.
В Блоке Дополнительная информация будет информация о платеже от платежной системы для менеджера.

После оплаты клиентом заказа, статус заказа становится _Оплачен_.
В блоке Дополнительная информация будет содержаться инфoрмация о платеже от платежной системы.

Найти созданный или оплаченный в ЕРИП счёт можно в личном кабинете платёжной
системы, используя значение UID из блока Дополнительная информация.

### Письмо-инструкция по оплате

В случае успешного изменения статуста заказа в _[ЕРИП] Ожидание оплаты_, клиенту
высылается письмо-инструкция следующего содержания:

```
Здравствуйте, Иван Иванов!

В этом письме содержится инструкция как оплатить заказ номер 29 в магазине Тестовый магазин через систему ЕРИП.

Если Вы осуществляете платеж в кассе банка, пожалуйста, сообщите кассиру о необходимости проведения платежа через систему "Расчет"(ЕРИП).

В каталоге сиcтемы "Расчет" услуги ООО Тест находятся в разделе:

ЕРИП - Тест - Магазин ООО Тест

Для проведения платежа необходимо:

1. Выбрать пункт Система "Расчет (ЕРИП)".
2. Выбрать последовательно вкладки: ЕРИП - Тест - Магазин ООО Тест.
3. Ввести номер заказа 29.
4. Проверить корректность информации.
5. Совершить платеж.


С уважением,
администрация Интернет-магазина
E-mail: sale@bitrix.local
```

## Тестовые данные

Вы можете использовать следующие данные, чтобы настроить способ оплаты в
тестовом режиме:

  * Домен API __api.bepaid.by__
  * ID магазина __336__
  * Ключ магазина __28421dbd7ec390c927b909d3deecffde90b848c1f95693922e2b73862ececc0e__
  * Номер сервиса в системе ЕРИП __99999999__

При использовании _Номера сервиса в системе ЕРИП_ __99999999__ через несколько секунд придет уведомление об успешной оплате.

## Примечания

Разработанно и протестированно с 1С-Битрикс 15.0.x/15.5.x
