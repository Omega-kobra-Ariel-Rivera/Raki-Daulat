#!/usr/bin/env bash
# Pruebas de integración de la API.
# Requiere el sitio corriendo con PHP y la base de datos con el esquema cargado.
# Uso: BASE_URL=http://localhost:8000 bash tests/api_test.sh

set -uo pipefail
BASE_URL="${BASE_URL:-http://localhost:8000}"
pass=0; fail=0

# Próxima fecha válida (no lunes), dentro de 7 días
fecha=$(python3 -c "import datetime as d;t=d.date.today()+d.timedelta(days=7);t+=d.timedelta(days=1) if t.isoweekday()==1 else d.timedelta();print(t)")
lunes=$(python3 -c "import datetime as d;t=d.date.today()+d.timedelta(days=1);t+=d.timedelta(days=(7-t.isoweekday()+1)%7);print(t)")

check() { # descripción, código esperado, texto esperado, curl args...
  local desc="$1" code="$2" expect="$3"; shift 3
  local out status body
  out=$(curl -s -w '\n%{http_code}' "$@")
  status=$(tail -n1 <<<"$out"); body=$(sed '$d' <<<"$out")
  if [[ "$status" == "$code" && "$body" == *"$expect"* ]]; then
    echo "  ✔ $desc"; pass=$((pass+1))
  else
    echo "  ✘ $desc (HTTP $status) → $body"; fail=$((fail+1))
  fi
}

echo "Contacto"
check "acepta un mensaje válido" 201 '"ok":true' -X POST "$BASE_URL/api/contacto.php" \
  -d nombre="Ana Pérez" -d correo="ana@example.com" -d telefono="+56 9 1234 5678" -d mensaje="Quisiera cotizar un evento para 30 personas."
check "rechaza correo inválido" 422 '"correo"' -X POST "$BASE_URL/api/contacto.php" \
  -d nombre="Ana" -d correo="no-es-correo" -d mensaje="Mensaje de prueba largo"
check "rechaza GET" 405 'Método no permitido' "$BASE_URL/api/contacto.php"
check "no guarda bots (honeypot)" 200 '"ok":true' -X POST "$BASE_URL/api/contacto.php" \
  -d website="spam.com" -d nombre="x"
check "resiste inyección SQL" 201 '"ok":true' -X POST "$BASE_URL/api/contacto.php" \
  -d nombre="Robert'); DROP TABLE contactos;--" -d correo="bobby@example.com" -d mensaje="Prueba de seguridad de la API"

echo "Reserva"
check "acepta una reserva válida" 201 '"ok":true' -X POST "$BASE_URL/api/reserva.php" \
  -d nombre="Luis Soto" -d correo="luis@example.com" -d telefono="912345678" \
  -d fecha="$fecha" -d hora="20:30" -d personas=4 -d comentarios="Aniversario"
check "rechaza los lunes" 422 'lunes' -X POST "$BASE_URL/api/reserva.php" \
  -d nombre="Luis Soto" -d correo="luis@example.com" -d telefono="912345678" \
  -d fecha="$lunes" -d hora="20:30" -d personas=4
check "rechaza fecha pasada" 422 'anterior a hoy' -X POST "$BASE_URL/api/reserva.php" \
  -d nombre="Luis Soto" -d correo="luis@example.com" -d telefono="912345678" \
  -d fecha="2020-01-07" -d hora="20:30" -d personas=4
check "rechaza hora fuera de horario" 422 '"hora"' -X POST "$BASE_URL/api/reserva.php" \
  -d nombre="Luis Soto" -d correo="luis@example.com" -d telefono="912345678" \
  -d fecha="$fecha" -d hora="03:00" -d personas=4
check "rechaza más de 12 personas" 422 '"personas"' -X POST "$BASE_URL/api/reserva.php" \
  -d nombre="Luis Soto" -d correo="luis@example.com" -d telefono="912345678" \
  -d fecha="$fecha" -d hora="20:30" -d personas=40

echo
echo "Resultado: $pass correctas, $fail con error"
[[ $fail -eq 0 ]]
