import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { MantineProvider } from '@mantine/core';
import { Notifications } from '@mantine/notifications';
import {QueryClientProvider} from "@tanstack/react-query";
import { App } from './App';
import { theme } from './theme';
import {queryClient} from "./queryClient";

import '@mantine/core/styles.layer.css';
import '@mantine/notifications/styles.css';
import './theme/styles/font.css';
import './theme/styles/global.css';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <MantineProvider theme={theme} defaultColorScheme='auto' cssVariablesSelector=":root">
        <QueryClientProvider client={queryClient}>
          <Notifications position="top-right" />
          <App />
        </QueryClientProvider>
      </MantineProvider>
    </BrowserRouter>
  </StrictMode>
);
