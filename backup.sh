#!/bin/bash
cp app.js backup/app.js/app.js.$(date +%Y%m%d-%H%M%S)
echo "✅ Backup app.js tersimpan: backup/app.js/app.js.$(date +%Y%m%d-%H%M%S)"

