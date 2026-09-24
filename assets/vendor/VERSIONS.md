# Bibliothèques embarquées

Copies exactes des fichiers publiés sur le registre npm, sans modification. Chaque archive a été vérifiée contre l'empreinte `dist.integrity` (sha512) publiée par le registre avant extraction. Les fichiers sont identiques octet pour octet à ceux que servait unpkg.com jusqu'à la version 2.8.1.

`tests/AssetsTest.php` vérifie que chaque fichier ci-dessous existe avec cette empreinte sha256, et qu'aucun fichier non inventorié n'est présent.

Mise à jour : télécharger l'archive de la nouvelle version depuis `registry.npmjs.org`, vérifier son intégrité, copier les mêmes fichiers dans un dossier `<bibliothèque>-<version>/`, mettre à jour ce tableau et les chemins de `src/Plugin.php` (VENDOR_STYLES, VENDOR_SCRIPTS).

| Fichier | Bibliothèque | Version | Licence | Origine (archive npm → chemin) | sha256 |
|---|---|---|---|---|---|
| `leaflet-1.9.4/leaflet.css` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/leaflet.css` | a7837102824184820dfa198d1ebcd109ff6d0ff9a2672a074b9a1b4d147d04c6 |
| `leaflet-1.9.4/leaflet.js` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/leaflet.js` | db49d009c841f5ca34a888c96511ae936fd9f5533e90d8b2c4d57596f4e5641a |
| `leaflet-1.9.4/LICENSE` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/LICENSE` | 53e8dc25862014e4324741ca18fbe3611e11d42ef69f59f86ea8c5389647d4cb |
| `leaflet-1.9.4/images/layers-2x.png` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/images/layers-2x.png` | 066daca850d8ffbef007af00b06eac0015728dee279c51f3cb6c716df7c42edf |
| `leaflet-1.9.4/images/layers.png` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/images/layers.png` | 1dbbe9d028e292f36fcba8f8b3a28d5e8932754fc2215b9ac69e4cdecf5107c6 |
| `leaflet-1.9.4/images/marker-icon-2x.png` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/images/marker-icon-2x.png` | 00179c4c1ee830d3a108412ae0d294f55776cfeb085c60129a39aa6fc4ae2528 |
| `leaflet-1.9.4/images/marker-icon.png` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/images/marker-icon.png` | 574c3a5cca85f4114085b6841596d62f00d7c892c7b03f28cbfa301deb1dc437 |
| `leaflet-1.9.4/images/marker-shadow.png` | Leaflet | 1.9.4 | BSD-2-Clause | https://registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz → `package/dist/images/marker-shadow.png` | 264f5c640339f042dd729062cfc04c17f8ea0f29882b538e3848ed8f10edb4da |
| `leaflet.markercluster-1.4.1/leaflet.markercluster.js` | Leaflet.markercluster | 1.4.1 | MIT | https://registry.npmjs.org/leaflet.markercluster/-/leaflet.markercluster-1.4.1.tgz → `package/dist/leaflet.markercluster.js` | 58be871df61f6c512464e15db0941e63b9491bf1396a2ae3bea6f39e0854cd1c |
| `leaflet.markercluster-1.4.1/MarkerCluster.css` | Leaflet.markercluster | 1.4.1 | MIT | https://registry.npmjs.org/leaflet.markercluster/-/leaflet.markercluster-1.4.1.tgz → `package/dist/MarkerCluster.css` | f9b756b96397305917d2ff42bebdce58294f89879f0d0cfd18664fffbc59c5d7 |
| `leaflet.markercluster-1.4.1/MarkerCluster.Default.css` | Leaflet.markercluster | 1.4.1 | MIT | https://registry.npmjs.org/leaflet.markercluster/-/leaflet.markercluster-1.4.1.tgz → `package/dist/MarkerCluster.Default.css` | 2d687359a406651b1616bac9c60fba667f134fce24d3fb6bb621c173aa9c1a96 |
| `leaflet.markercluster-1.4.1/MIT-LICENCE.txt` | Leaflet.markercluster | 1.4.1 | MIT | https://registry.npmjs.org/leaflet.markercluster/-/leaflet.markercluster-1.4.1.tgz → `package/MIT-LICENCE.txt` | c14f7ab1a0cfd6bb5c686802a553b182d7fe4ff2f02592be9fadd1c50526b76e |
| `maplibre-gl-3.6.2/LICENSE.txt` | MapLibre GL JS | 3.6.2 | BSD-3-Clause | https://registry.npmjs.org/maplibre-gl/-/maplibre-gl-3.6.2.tgz → `package/dist/LICENSE.txt` | ee5fc05a0677eaf69601d2c7db0d9ecd6cc27c3abc1d0733bc9ed34707cf8ef2 |
| `maplibre-gl-3.6.2/maplibre-gl.css` | MapLibre GL JS | 3.6.2 | BSD-3-Clause | https://registry.npmjs.org/maplibre-gl/-/maplibre-gl-3.6.2.tgz → `package/dist/maplibre-gl.css` | 731181d400d65a8b09d842f55b70bc4dc11010b15b8549e2c65a69d233fbdd2e |
| `maplibre-gl-3.6.2/maplibre-gl.js` | MapLibre GL JS | 3.6.2 | BSD-3-Clause | https://registry.npmjs.org/maplibre-gl/-/maplibre-gl-3.6.2.tgz → `package/dist/maplibre-gl.js` | c46084df69bbaa995b301a515274a86ec53905c78459b80dccbc27a0c0b8d13b |
| `maplibre-gl-leaflet-0.0.22/leaflet-maplibre-gl.js` | maplibre-gl-leaflet | 0.0.22 | ISC | https://registry.npmjs.org/@maplibre/maplibre-gl-leaflet/-/maplibre-gl-leaflet-0.0.22.tgz → `package/leaflet-maplibre-gl.js` | 59ecd8d2b32779d24f1bd26b87c8890f27e4a6e7858d993bd17e4d878e6d2677 |
| `maplibre-gl-leaflet-0.0.22/LICENSE` | maplibre-gl-leaflet | 0.0.22 | ISC | https://registry.npmjs.org/@maplibre/maplibre-gl-leaflet/-/maplibre-gl-leaflet-0.0.22.tgz → `package/LICENSE` | eaa721ba158cbeff47ad53b1035dfc26ff744df66662c93ff715c9885197ebf3 |
