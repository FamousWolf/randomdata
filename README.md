# TYPO3 Extension `randomdata`
TYPO3 extensions to generate new random data or replace existing data with random data

This extensions uses https://github.com/fzaninotto/Faker and was loosely based on https://github.com/georgringer/faker. Thanks go out to the builders, contributors and maintainers of those projects.

## Requirements
- TYPO3 CMS 8.7 or 9.5
- PHP 7+
- Licence: GPL 3.0

## Manual
After installing randomdata in TYPO3 you can run it using the following command:

```
vendor/bin/typo3 randomdata:generate configuration.yaml
```

For more information about the command line options, use the following command: 

```
vendor/bin/typo3 help randomdata:generate
```

The location of the configuration yaml file needs to be inside the site root.

For each record type you want to add to a PID you have to add configuration to the yaml file. The configuration for a single record type in a single pid looks like this:

```yaml
recordTypeName:
  table: recordTable
  pid: recordPid
  action: action
  count: numberOfRecordsToCreate
  fields:
    field1:
      provider: Provider
```

- **recordTypeName** is just a label. It can be anything you want. For example `news`
- **table** is the database table for the record. This table has to be known in TCA. For example `tx_news_domain_model_news`
- **pid** is the UID of the page for the record. For example `4`
- **action** is the action to perform. By default only 2 actions are possible: `insert` to create new records and `replace` to replace all existing records of this type for that pid. It is also possible to create custom actions.
- **count** is the number of records to create. This is only needed for the action `insert`. For example `10`
- **fields** contains the configuration for the fields of the records.
- **field1** is the name of a field of the record. For example `title`
- **provider** is the name of the Provider to use when generating the random data. For example `Words`.

A lot of Providers also require additional configuration. These can be placed on the same level as the Provider.

### Providers
The following providers are available by default:

- **Barcode**
    - type: The type of barcode [aen13 (default), aen8, isbn10, isbn13]
- **Boolean**
- **City**
- **Color**
    - type: The type of color [hexColor (default), rgbColor, rgbCssColor, colorName, safeColorName]
- **Company**
- **CountryCode**
- **Country**
- **CreditcardExpirationDate**
- **CreditcardNumber**
- **CreditcardType**
- **CurrencyCode**
- **DateTime**
    - minimum: The minimum date in any valid date/time format
    - maximum: The maximum date in any valid date/time format
    - timezone: The timezone
    - format: The format as set for the PHP date() function
- **Domain**
    - type: The type of domain [domainName (default), safeEmailDomain, freeEmailDomain, tld]
- **Email**
    - type: The type of e-mail address [email (default), safeEmail, freeEmail, companyEmail]
- **Emoji**
- **FileExtension**
- **File**
    - minimum: Minimum number
    - maximum: Maximum number
    - source: Source directory containing files
    - referenceFields: Fields in the file reference
- **FirstName**
    - gender: Gender of the name [null (default), male, female]
- **FixedValue**
    - value: The value
- **Float**
    - minimum: Minimum number
    - maximum: Maximum number
    - decimals: Maximum number of decimals
- **FullAddress**
- **Hash**
    - type: Type of the hash [sha1 (default), sha256, md5]
- **Iban**
    - country: Country for the Iban
- **Integer**
    - minimum: Minimum number
    - maximum: Maximum number
- **Ip**
    - type: The type of IP address [ipv4 (default), ipv6, localIpv4]
- **JobTitle**
- **LanguageCode**
- **LastName**
- **Locale**
- **MimeType**
- **Name**
    - gender: The gender of the name [null (default), male, female]
    - addTitle: Add a title to the name [false (default), true]
- **Paragraphs**
    - minimum: The minimum number of paragraphs
    - maximum: The maximum number of paragraphs
    - sentences: The approximate number of sentences (could be a few more or less randomly)
    - html: Run through htmlSpecialChars and add `<p>` and `</p>` tags [false (default), true]
- **PhoneNumber**
    - e164: The phone number should be in e164 format [false (default), true]
- **Postcode**
- **RandomValue**
    - values: Array of values
- **Relation**
    - table: The table to select the relation from
    - pid: The pid to select the relation from
    - minimum: The minumum number
    - maximum: The maximum number
- **Sentences**
    - minimum: The minimum number
    - maximum: The maximum number
- **State**
- **StreetAddress**
- **Street**
- **SwiftBic**
- **Text**
    - maximum: The maximum number
- **Title**
    - gender [null (default), male, female]
- **Url**
- **UserAgent**
    - type: The browser type [null (default), chrome, firefox, safari, opera]
- **Uuid**
- **Words**
    - minimum: The minimum number
    - maximum: The maximum number

### Example configuration yaml
```yaml
categories:
  table: sys_category
  pid: 4
  action: insert
  count: 10
  fields:
    title:
      provider: Words
      minimum: 1
      maximum: 3

news:
  table: tx_news_domain_model_news
  pid: 4
  action: insert
  count: 20
  fields:
    title:
      provider: Sentences
      minimun: 1
      maximum: 1
    teaser:
      provider: Sentences
      minimum: 1
      maximum: 30
    bodytext:
      provider: Paragraphs
      minimum: 1
      maximum: 10
      html: true
    datetime:
      provider: DateTime
      minimum: -1 year
      maximum: now
      format: U
    categories:
      provider: Relation
      table: sys_category
      minimum: 0
      maximum: 5
    fal_media:
      provider: File
      minimum: 0
      maximum: 1
      source: fileadmin/randomimages/
      referenceFields:
        showinpreview:
          provider: FixedValue
          value: 1
    path_segment:
      provider: FixedValue
      value:
```

## Custom Provider
You can create a custom provider from your own extension by adding a class which implements `\WIND\Randomdata\Provider\ProviderInterface`. It should have at least a static `generate` method.

You can set your custom provider in the configuration yaml file by setting the full class name in the `provider` option. For example `provider: \My\Custom\Provider`

## Custom action
If you need anything other than `insert` or `replace` as action, you can use the `generateItemCustomAction` signal slot. You also need to set your action in the `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['randomdata']['allowedActions']` array.

## @todo / missing features

- **HTML provider** A provider to generate HTML data. Perhaps random text filling a template.
- **Unit tests** Unit tests have to added for all providers.
