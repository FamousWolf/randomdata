# TYPO3 Extension `randomdata`
TYPO3 extensions to generate new random data or replace existing data with random data

This extensions uses https://github.com/fzaninotto/Faker and is loosely based on https://github.com/georgringer/faker. Thanks go out to the builders, contributors and maintainers of those projects.

This manual is still a work in progress. It is not complete.

## Requirements
- TYPO3 CMS 8.6 or 9.5
- PHP 7+
- Licence: GPL 3.0

## Manual
After installing randomdata in TYPO3 you can run it using the following command:

```
vendor/bin/typo3 randomdata:generate configuration.yaml
```

The location of the configuration yaml file needs to be inside the site root and reletive to it. For example: `typo3conf/ext/myext/Resources/Private/Yaml/randomDataConfiguration.yaml`

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
    path_segment:
      provider: FixedValue
      value:
```

## @todo / missing features

### Unit tests
Unit tests have to added for all providers.

### File provider
A provider to add files to records. This should work for FAL fields and non-FAL fields and should allow the creation of at least images, text and (random) binary files.

### HTML provider
A provider to generate HTML data.

### Manual
This manual is far from complete. The following still needs to be added:

- Description of the configuration yaml file
- Reference to all configuration options
- Description and reference of all available providers
- Description of how to create custom actions
- Description of how to create custom providers
