#!/bin/bash
echo "🚀 Iniciando servidor NomadApp..."
echo "✅ Límites de subida configurados a 10MB"
php -d upload_max_filesize=10M -d post_max_size=12M -S localhost:8000