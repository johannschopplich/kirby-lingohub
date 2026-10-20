<?php

declare(strict_types = 1);

namespace JohannSchopplich\Lingohub;

final class PanelContext
{
    /**
     * Reports which options are set, without their values, so the API key
     * never reaches the Panel.
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
