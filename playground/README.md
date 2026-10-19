# Playground

A multi-language site (German as the default, English and Portuguese) whose blueprints cover every field type the plugin flattens: blocks, nested blocks, layouts, structures and objects. Every page sends you to the Panel, `/panel/login` signs in as admin, `/panel/login?role=playground` as the role that may update the Site but neither pages nor any title, so a download into a page is refused.

Uploads and downloads need a Lingohub project. Copy `.env.example` to `.env` and fill in `LINGOHUB_API_KEY`, `LINGOHUB_WORKSPACE_ID` and `LINGOHUB_PROJECT_ID`; [Setup](../README.md#setup) explains where each comes from.

`composer dev` serves it on `localhost:8000`. The admin account `admin@example.com` has no password; only the login route signs it in.

## What the views test

| View      | Set up for                                                                                                      |
| --------- | --------------------------------------------------------------------------------------------------------------- |
| `site`    | View button only, the Site's resource under `site/`                                                             |
| `default` | View button and `lingohub-status` section; blocks with nested blocks, an object, a structure with layouts, tags |
| `note`    | View button and section on a child page, a blocks field                                                         |
