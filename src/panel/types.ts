export type LingohubSegmentStatus =
  "APPROVED" | "DRAFT" | "NOT_TRANSLATED" | "TRANSLATED";

/** One language file of a Lingohub resource, as reported by the project status. */
export interface LingohubResourceFile {
  name: string;
  languageCode: string;
  statuses: Record<LingohubSegmentStatus, number>;
  failingLingochecksCount: number;
  segmentsUpdatedAt: string;
}

export interface LingohubTranslationStatus {
  resourceFiles?: {
    files: LingohubResourceFile[];
  }[];
}

export interface PluginContext {
  config: {
    hasApiKey: boolean;
    hasWorkspaceId: boolean;
    hasProjectId: boolean;
  };
  languages: Record<string, { locale?: string[] }>;
}

export interface ModelContext {
  availableTranslationLanguageCodes: string[];
}
