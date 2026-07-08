export interface Redirect {
  path: string;
  /** Строкой — со строками работает селект. */
  code: string;
  destination: string;
}