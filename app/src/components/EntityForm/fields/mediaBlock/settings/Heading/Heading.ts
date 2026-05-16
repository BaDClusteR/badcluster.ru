export default function Heading(text: string): HTMLElement {
  const headingEl = document.createElement("div");
  headingEl.className = 'ce-popover-item__settings-label';
  headingEl.textContent = text;
  return headingEl;
}
