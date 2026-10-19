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
}

export interface ModelContext {
  /** Each language's Lingohub resource file, keyed by language code. */
  resourceFiles: Record<string, string>;
  /** Secondary languages the model has content in. */
  translatedLanguageCodes: string[];
}
