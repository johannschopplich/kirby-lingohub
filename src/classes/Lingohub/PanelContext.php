<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

final class PanelContext
{
    /**
     * Builds the plugin configuration the Panel receives. The Panel only ever
     * needs to know these options are set, never their values.
     *
     * @return array<string, bool>
     */
    public static function config(): array
    {
        return [
            'hasApiKey' => Lingohub::hasUsableOption('apiKey'),
            'hasWorkspaceId' => Lingohub::hasUsableOption('workspaceId'),
            'hasProjectId' => Lingohub::hasUsableOption('projectId')
        ];
    }
}
