/*
 * Rizky Moto Shop — Global JS Entry
 *
 * ui-system.js:
 * - sidebar
 * - mobile sidebar
 * - notification
 * - profile
 * - universal UI
 *
 * toast.js harus dimuat sebelum ui-system.js.
 */

import './dashboard/toast.js';
import './dashboard/ui-system.js';
import './passkeys.js';

import { rmsImageGenerator } from './dashboard/generator.js';

window.rmsImageGenerator = rmsImageGenerator;

import './dashboard/settings-openai.js';