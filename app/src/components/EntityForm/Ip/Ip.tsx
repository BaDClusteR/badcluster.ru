import {GeoIp} from "@admin/types";
import {ReactNode} from "react";
import classes from "./Ip.module.css";
import {getStaticRoot} from "@/providers/AppSettingsProvider.ts";
import {Popover} from "@mantine/core";
import {useDisclosure} from "@mantine/hooks";

export default function Ip(
  {info}: {info?: GeoIp}
): ReactNode {
  if (!info) {
    return null;
  }

  let flag: ReactNode = null;
  const [opened, { close, open }] = useDisclosure(false);

  if (info.countryCode && info.countryCode !== '-') {
    flag = <img
      src={`${getStaticRoot()}/images/flags/${info.countryCode.toLowerCase()}.svg`}
      alt={info.country}
      className={classes.flag}
    />
  }

  return <div className={classes.container}>
    {
      flag &&
      <Popover position="top" withArrow shadow="md" opened={opened}>
        <Popover.Target>
          <span className={classes.flag} onMouseEnter={open} onMouseLeave={close}>{flag}</span>
        </Popover.Target>
        <Popover.Dropdown className={classes.popover}>
          <span className={classes.place}>{info.city}, {info.country}</span>
          <span className={classes.range}>{info.rangeStart} — {info.rangeEnd}</span>
        </Popover.Dropdown>
      </Popover>
    }
    <span className={classes.text}>{info.ip}</span>
  </div>
}
