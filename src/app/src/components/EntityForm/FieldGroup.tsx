import {useState, ReactNode, useEffect} from "react";
import classes from "./FieldGroup.module.css";
import clsx from "clsx";
import {IconPencil} from "@tabler/icons-react";
import {ActionIcon} from "@mantine/core";

export default function FieldGroup({children}: {children: ReactNode}) {
  const [fullscreen, setFullscreen] = useState<boolean>(false);

  useEffect(() => {
    const keydownHandler = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setFullscreen(false);
        e.preventDefault();
      }
    }

    if (fullscreen) {
      document.addEventListener('keydown', keydownHandler);
    }

    return () => {
      document.removeEventListener('keydown', keydownHandler);
    }
  }, [fullscreen]);

  return <div className={clsx(classes.group, fullscreen && classes.fullscreen)}>
    <div className={classes.content}>
      {children}
    </div>

    <ActionIcon
      onClick={() => {
        setFullscreen(!fullscreen);
      }}
      variant="subtle"
      aria-label="Переключить полноэкранный режим"
      className={classes.fullscreenAction}
    >
      <IconPencil size={16} />
    </ActionIcon>
  </div>
}
