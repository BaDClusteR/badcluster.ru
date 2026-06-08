// @ts-expect-error — @editorjs/paragraph has no types
import Paragraph from "@editorjs/paragraph";

/**
 * Custom Paragraph that allows saving empty blocks.
 * Editor.js by default skips blocks where validate() returns false.
 */
export class ParagraphBlock extends Paragraph {
  validate() {
    return true;
  }
}