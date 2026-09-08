import {build} from 'esbuild';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
const here=path.dirname(fileURLToPath(import.meta.url));
await build({entryPoints:[path.join(here,'header.tsx')],bundle:true,format:'iife',jsx:'automatic',minify:true,legalComments:'inline',define:{'process.env.NODE_ENV':'"production"'},alias:{'@/lib/utils':path.join(here,'utils.ts')},outfile:path.join(here,'../source/assets/js/header-lucide.bundle.js')});
