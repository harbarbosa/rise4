export function reportAppError(error: unknown, context: Record<string, unknown> = {}) {
  console.error("[AlfaHP Mobile] erro de aplicação", { error, ...context });
}
