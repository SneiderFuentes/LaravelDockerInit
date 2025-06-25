#!/usr/bin/env bash
set -e

echo ">>> Creando bases de datos y otorgando privilegios..."

mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
  CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
#   CREATE DATABASE IF NOT EXISTS \`center_a\`         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
#   CREATE DATABASE IF NOT EXISTS \`center_b\`         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

  GRANT ALL PRIVILEGES ON \`$MYSQL_DATABASE\`.* TO '$MYSQL_USER'@'%';
#   GRANT ALL PRIVILEGES ON \`center_a\`.*        TO '$MYSQL_USER'@'%';
#   GRANT ALL PRIVILEGES ON \`center_b\`.*        TO '$MYSQL_USER'@'%';
  FLUSH PRIVILEGES;
EOSQL