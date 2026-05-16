import classes from "./ImageSizes.module.css";
import TextField from "../TextField/TextField.ts";

export default function ImageSizes(
  {
    width,
    height,
    onWidthChange,
    onHeightChange
  }: {
    width: number,
    height: number,
    onWidthChange: (width: number) => void,
    onHeightChange: (height: number) => void
  }
): HTMLDivElement {
  const wrapper = document.createElement("div");
  wrapper.className = classes.wrapper;

  wrapper.appendChild(
    TextField({
      placeholder: "Ширина",
      value: width ? width.toString() : "",
      onChange: (value: string) => {
        onWidthChange(
          parseInt(value) || 0
        );
      }
    })
  );

  const divider = document.createElement("div");
  divider.className = classes.divider;
  divider.innerHTML = '&times;';

  wrapper.appendChild(divider);

  wrapper.appendChild(
    TextField({
      placeholder: "Высота",
      value: height ? height.toString() : "",
      onChange: (value: string) => {
        onHeightChange(
          parseInt(value) || 0
        );
      }
    })
  );

  return wrapper;
}
