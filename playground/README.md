# Playground

A multi-language site (German as the default, English and Portuguese) whose blueprints cover every field type the plugin flattens: blocks, nested blocks, layouts, structures and objects. The `lingohub` view button sits on the Site and the page views, the `lingohub-status` section on the pages.

Uploads and downloads need a Lingohub project. Copy `.env.example` to `.env` and fill in `LINGOHUB_API_KEY`, `LINGOHUB_WORKSPACE_ID` and `LINGOHUB_PROJECT_ID`; [Setup](../README.md#setup) explains where each comes from.

`composer dev` serves the playground on `localhost:8000`. The first visit to `/panel` creates the admin account.
