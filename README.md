![Kirby Lingohub preview](./.github/kirby-lingohub.png)

# Kirby Lingohub

The Kirby Lingohub plugin integrates the [Lingohub](https://lingohub.com) translation service into your Kirby website. The plugin allows you to upload content from Kirby to Lingohub for translation and download the translations back to Kirby.

> [!NOTE]
> For this plugin to work, you need to have a Lingohub account and a project set up. You can create a free account at [Lingohub](https://lingohub.com/).

## Requirements

- Kirby 4 or Kirby 5

Kirby is not free software. However, you can try Kirby and the Starterkit on your local machine or on a test server as long as you need to make sure it is the right tool for your next project. … and when you're convinced, [buy your license](https://getkirby.com/buy).

## Installation

### Composer

```bash
composer require johannschopplich/kirby-lingohub
```

### Download

Download and copy this repository to `/site/plugins/kirby-lingohub`.

## Getting Started

### 1️⃣ Kirby Language Configuration

Set up a multi-language Kirby site. For each desired language, create a language file in the `site/languages` folder. The language file should contain the following information:

```php
# /site/languages/en.php
return [
    'code' => 'en',
    'default' => true,
    'direction' => 'ltr',
    'locale' => 'en_US',
    // Or if you need multiple locales:
    // 'locale' => [
    //     'LC_ALL' => 'en_US.UTF-8',
    // ]
    'name' => 'English'
];
```

> [!NOTE]
> Make sure to set the `locale` option to the correct locale for each language. This is important for the mapping between Kirby and Lingohub.

### 2️⃣ Lingohub Setup

First, create a new project in your Lingohub workspace. Use the file format **JSON (Standard)**:

![Lingohub platform format](./.github/lingohub-platform-format.png)

Now, configure the project to use the same source and target languages as you have in Kirby. The language code in Lingohub should match the locale in Kirby. From the example above, the locale in Kirby is `en_US`. Set the language code in Lingohub to `en-US`. (Note the difference in the delimiter.)

As another example: If the Kirby language code is `de` and the locale is `de_DE`, the Lingohub language code should be `de-DE`.

Finally, create an API key for your workspace. Follow the [Linoghub API key guide](https://help.lingohub.com/en/articles/6775959-how-to-create-an-api-key) to create an API key with read/write access.

> [!NOTE]
> Enable full permissions for the **Resources** section in the API key settings. This is required to upload and download translations. No other permissions are needed.

### 3️⃣ Kirby Plugin Configuration

After setting up the languages in Kirby and Lingohub, configure the plugin in the `site/config/config.php` file.

- To retrieve the workspace ID, open the Lingohub dashboard and copy the workspace ID from the URL. If the URL contains `workspace/space_16kPs3bRIpXi-29323/dashboard`, the workspace ID is `space_16kPs3bRIpXi-29323`.
- For the project ID, open the project in Lingohub and copy the project ID from the URL. If the URL contains `project/pr_18JCETCbT9NW-31003/branches`, the project ID is `pr_18JCETCbT9NW-31003`.

```php
# /site/config/config.php
return [
    'languages' => true,

    'johannschopplich.lingohub' => [
        'apiKey' => '<LINGOHUB_API_KEY>',
        'workspaceId' => 'space_123-456',
        'projectId' => 'pr_123-456',
    ]
];
```

### 4️⃣ Blueprint Configuration

Kirby 5 introduces new extensions that allow you to add custom view buttons to most Panel views (e.g. page, site, or file). The Lingohub plugin provides a dropdown button that can be added alongside the default buttons, such as the languages dropdown button.

To add the `lingohub` dropdown button to a particular view, set the `buttons` option in the corresponding blueprint. The following example shows how to reference the default buttons and add the `lingohub` button to a page blueprint:

```yml
title: Note

buttons:
  - preview
  - settings
  - languages
  - lingohub
  - status
```

> [!NOTE]
> Kirby 4 does not support custom view buttons, but the `lingohub` button has been backported. It is always placed after the language dropdown.

Finalize your blueprints by adding the necessary translation configuration to each field, e.g. `translate: false` if a field should not be translated. The plugin will skip these fields when uploading content to Lingohub.

## Usage

## Translation Status

The [translation status](https://help.lingohub.com/en/articles/6683154-manage-localization-with-statuses) is automatically retrieved from Lingohub and displayed in the user interface. It shows the minimum status of all segments of the page. Only if all segments are in status **Approved**, the translation language will be shown as **Approved** in green color:

![Kirby Lingohub status approved](./.github/kirby-lingohub-status-approved.png)

Click on the status to open a dialog with more detailed information:

![Kirby Lingohub status dialog](./.github/kirby-lingohub-status-dialog.png)

## Upload Translations for a Page or File

Click on the **Lingohub** dropdown button in the Panel header and select **Upload Translations**.

![Kirby Lingohub upload translations](./.github/kirby-lingohub-upload.png)

As soon as you click on the button, the plugin will:

- Serialize and upload the default language content to Lingohub.
- If the current model (page or file) has Kirby translations, the plugin will check if these translations already exist in Lingohub. If not, they will be uploaded automatically. If they exist, the user can decide if they should be uploaded.

Instruct your translators to translate the content in Lingohub. When the translations are done, download the translations back to Kirby.

## Download Translations from Lingohub

Click on the **Lingohub** dropdown button in the Panel header and select **Download Translations**. This will open a dialog where you can select the languages and status of the translations to download:

![Kirby Lingohub download translations](./.github/kirby-lingohub-download.png)

The dialog contains the following fields:

- **Status**: Define the minimum status of the segments to download. The default is **Approved**.
- **Languages**: Select the languages to download. By default, all languages with a status of 100% approved in Lingohub are preselected.

Submit the form to download the translations. The page will be updated with the new translations.

## Sections

### Translation Status Table

![Kirby Lingohub status section](./.github/kirby-lingohub-status-section.png)

The status section is similar to [Lingohub's statuses overview](https://help.lingohub.com/en/articles/6788499-words-and-segments-overview) and displays the translation status for all available languages. The status section is read-only and cannot be edited.

To add the status section to a blueprint, use the following configuration:

```yml
sections:
  lingohubStatus:
    type: lingohub-status
```

## FAQ

### How Is Kirby Content Data Mapped to Lingohub?

With the JSON (Standard) file format, Lingohub requires a key-value hierarchy structure where all elements in the chain have a long-term consistent key. Thus, nested data structures like `block` fields or `structure` fields in Kirby must be flattened to a key-value map. For example, block fields are transformed into a key-value map where the key is generated from the field name, block type, and block field name: `{fieldName}_{blockId}_{blockType}_{blockFieldName}`.

Lingohub uses a path based approach to identify the content. The path is generated from the Kirby model ID (which doesn't contain any draft status or sort numbers) and the language code.

While Kirby uses a language code as file suffix (e.g., `en`), Lingohub uses a locale code (e.g., `en-US`). The plugin maps the Kirby language code to the Lingohub locale code.

Some special rules apply when uploading content to Lingohub:

- Surrounding `<p>` tags are stripped from `writer` fields before uploading to Lingohub. This is done to remove unnecessary clutter from the translator's view in Lingohub. The tags are added back when the content is downloaded to Kirby.

## Roadmap

## Media Files

- Translate all media files (images, videos, etc.) associated with the page. This is a nice-to-have feature that may be implemented in the future.

## Mass Operations

- Upload all pages to Lingohub in bulk, including translations if they already exist.
- Download all pages from Lingohub in bulk. Configure which pages to process using a filter: e.g. only pages with a certain status or language.
- Iterate through all pages in Kirby and check if new translations are available in Lingohub. If so, download them.

## GEHT NICHT

- User sets which [status in Lingohub](https://help.lingohub.com/en/articles/6683154-manage-localization-with-statuses) the existing translations should have after uploading to lingohub. (GEHT NICHT!)

## TODO

- add "translate_only_externally: true" to all fields that should only be translatable using the external translation service (i.e. Lingohub). For these fields, only the source language is editable. In translations, these fields cannot be edited. Admins can always edit the fields, independent of this setting. Set it to true, when editors that should only edit translations in Lingohub, when the Lingohub translation memory is important to you.

## License

[MIT](./LICENSE) License © 2025-PRESENT [Johann Schopplich](https://github.com/johannschopplich)
