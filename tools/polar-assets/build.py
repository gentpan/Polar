"""Build local assets in the explicit, dependency-resolved bundle-order.json order."""
from pathlib import Path
import json,re,subprocess,urllib.parse
base=Path(__file__).resolve().parent
root=base.parents[1]
theme=root
src=base/'source'
order=json.loads((base/'bundle-order.json').read_text())
manifest={key:order[key] for key in ('styles','scripts')}
def css(text,url):
 def rewrite(match):
  value=match[1].strip(' \"\'')
  if value.startswith(('data:','#')):return match[0]
  resolved=urllib.parse.urljoin(url,value)
  if '/wp-content/themes/shanying/assets/' in resolved:resolved='../'+resolved.split('/wp-content/themes/shanying/assets/',1)[1]
  return 'url("'+resolved+'")'
 return re.sub(r'url\(([^)]+)\)',rewrite,text)
def stylesheet(handle,source):
 if source.startswith('https:'):
  filename={'feng-title-font':'title.css','feng-space-grotesk':'space.css'}.get(handle,source.rsplit('/',1)[-1])
  path=src/'remote'/filename;url=source
 else:path=src/source;url='http://xifeng.local/wp-content/themes/shanying/'+source
 return '\n/* '+handle+' */\n'+css(path.read_text(),url)
fonts=[];styles=[]
for handle,source in order['styles'].items():
 (fonts if 'font' in source or handle in ('feng-code-font','feng-space-grotesk') else styles).append(stylesheet(handle,source))
def script(handle,path,enabled):
 return '\n;/* '+handle+' */\nif(window.'+enabled+'.includes('+json.dumps(handle)+')){\n'+path.read_text()+'\n}\n'
bootstrap="\n;window.polarEnabledScripts=JSON.parse(document.currentScript?.dataset.shanyingModules||'[]');\n"
scripts=[bootstrap+'\n/*! SunCalc 1.9.0 — BSD-2-Clause\n'+(src/'vendor/suncalc/LICENSE').read_text()+'\n*/\n'+(src/'vendor/suncalc/suncalc.js').read_text()]
scripts += [script(handle,src/source,'polarEnabledScripts') for handle,source in order['scripts'].items()]
lucide=base/'lucide-motion'
subprocess.run(['node',str(lucide/'build.mjs')],check=True)
scripts.append('\n;/*! Lucide Animated\n'+(lucide/'LICENSE').read_text()+'\n*/\n'+(src/'assets/js/header-lucide.bundle.js').read_text())
scripts.append('\n;/* Statistic odometer */\n'+(src/'assets/js/stat-roll.js').read_text())
(theme/'assets/css/main.css').write_text(''.join(fonts+styles))
(theme/'assets/js/main.js').write_text(''.join(scripts))
for path in [theme/'inc/asset-bundles.json',base/'manifest.json']:path.write_text(json.dumps(manifest,indent=2))
admin=[bootstrap.replace('polarEnabledScripts','polarEnabledAdminScripts')]
admin += [script(handle,src/'assets/js'/name,'polarEnabledAdminScripts') for handle,name in order['admin_scripts'].items()]
(theme/'assets/js/admin.js').write_text(''.join(admin))
admincss=[(src/'assets/css'/name).read_text() for name in ('admin.css','admin-square.css')]
admincss.extend(stylesheet('admin-'+name,'assets/css/'+name) for name in ('tokens.css','editor.css','reading.css'))
(theme/'assets/css/admin.css').write_text('\n'.join(admincss))
(theme/'assets/css/admin-skin.css').write_text((src/'assets/css/admin-skin.css').read_text())
(theme/'assets/css/admin-square.css').write_text((src/'assets/css/admin-square.css').read_text())
print(len(styles),'CSS sections;',len(scripts),'JS sections')
