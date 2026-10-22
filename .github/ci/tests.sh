#!/usr/bin/env bash
#
# Tests de Croogo con PHP 8 y PHPUnit 9, cada archivo en su propio proceso.
#
#   .github/ci/tests.sh preparar   # arma ./app (croogo/app v2.3.2) e instala con composer
#   .github/ci/tests.sh            # corre y compara contra la línea base
#   .github/ci/tests.sh --update   # corre y reescribe la línea base
#   .github/ci/tests.sh -- Nodes   # sólo los archivos cuya ruta matchea (sin comparar)
#
# Por qué uno por vez: la suite entera en un proceso escondía tests que andaban sólo porque
# otro anterior había dejado una tabla o una clase cargada. Y un fatal no corta los demás.
#
# La base se elige con CROOGO_DB y CROOGO_DB_HOST (ver database.php).

set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CI="$RAIZ/.github/ci"
APP="$RAIZ/app"
BASELINE="$CI/tests.baseline"
SALIDAS="$APP/tmp/tests"
RESULTADO="$SALIDAS/resultado.tsv"
ESPERA=300

preparar() {
	if [ ! -d "$APP" ]; then
		git clone -q --depth 1 --branch v2.3.2 https://github.com/croogo/app.git "$APP"
	fi
	cp "$CI/composer.json" "$APP/composer.json"
	rm -f "$APP/composer.lock"
	cp "$CI/database.php" "$APP/Config/database.php"
	mkdir -p "$APP/etc"
	cp "$CI/phpunit_bootstrap.php" "$APP/etc/phpunit_bootstrap.php"
	cp "$CI/phpunit.xml.dist" "$APP/phpunit.xml.dist"
	cp "$APP/Config/croogo.php.install" "$APP/Config/croogo.php"
	# Con el salt y el cipherSeed de ejemplo Croogo se cree sin instalar y redirige todo a
	# /install (Croogo/Config/bootstrap.php): una instalación real tiene los suyos.
	sed -i "s/f78b12a5c38e9e5c6ae6fbd0ff1f46c77a1e3/$(head -c 20 /dev/urandom | sha1sum | cut -c1-40)/; s/60170779348589376/$(tr -dc 0-9 < /dev/urandom | head -c 20)/" "$APP/Config/croogo.php"
	# Los plugins que carga el test_app de Croogo, ni uno más: Translate engancha su behavior
	# a todos los modelos y pide la tabla i18n en cada test.
	php -r '
		$s = json_decode(file_get_contents($argv[1] . "/Config/settings.json.install"), true);
		$t = json_decode(file_get_contents($argv[2] . "/Croogo/Test/test_app/Config/settings.default"), true);
		$s["Hook"]["bootstraps"] = $t["Hook"]["bootstraps"];
		file_put_contents($argv[1] . "/Config/settings.json", json_encode($s, JSON_PRETTY_PRINT));
	' "$APP" "$RAIZ"
	(cd "$APP" && composer update --no-interaction --no-progress) || exit 1
	chmod -R 777 "$APP/tmp"
}

correr() {
	local filtro="$1"
	mkdir -p "$SALIDAS"
	: > "$RESULTADO"
	cd "$RAIZ"
	for archivo in $(git ls-files '*Test.php' | grep '/Test/Case/' | grep -v '/All' | grep -v '^Test/Case/' | grep -E "$filtro"); do
		local nombre salida codigo resumen estado fallas
		nombre="$(echo "$archivo" | tr / _)"
		salida="$(cd "$APP" && timeout "$ESPERA" Vendor/bin/phpunit --stderr "Vendor/croogo/croogo/$archivo" 2>&1)"
		codigo=$?
		printf '%s\n' "$salida" > "$SALIDAS/$nombre.txt"
		resumen="$(echo "$salida" | grep -aoE 'OK \([0-9]+ tests?, [0-9]+ assertions?\)|Tests: [0-9]+, Assertions: [0-9]+[^.]*' | tail -1)"
		if [ -z "$resumen" ]; then
			estado=ROTO; fallas=-
			resumen="$(echo "$salida" | grep -aE 'Fatal|Error|Exception' | head -1 | cut -c1-160)"
			[ $codigo -eq 124 ] && resumen="tardó más de ${ESPERA}s"
		elif [[ "$resumen" == OK* ]] || ! echo "$resumen" | grep -qE 'Errors|Failures'; then
			estado=OK; fallas=0
		else
			estado=FALLA
			fallas=$(( $(echo "$resumen" | grep -oE 'Errors: [0-9]+' | grep -oE '[0-9]+' || echo 0) + $(echo "$resumen" | grep -oE 'Failures: [0-9]+' | grep -oE '[0-9]+' || echo 0) ))
		fi
		printf '%s\t%s\t%s\t%s\n' "$estado" "$archivo" "$fallas" "$resumen" >> "$RESULTADO"
		printf '%-6s %s\n' "$estado" "$archivo"
	done
	# Algún test borra archivos del test_app y no los devuelve: que no quede el repo sucio.
	git ls-files -d -- Croogo/Test/test_app | xargs -r git checkout -q --
}

# OK < FALLA (menos fallas es mejor) < ROTO
peso() {
	case "$1" in
		OK) echo 0 ;;
		FALLA) echo $((1 + $2)) ;;
		*) echo 100000 ;;
	esac
}

comparar() {
	local peor=0 mejor=0
	while IFS=$'\t' read -r estado archivo fallas resumen; do
		local base base_estado base_fallas
		base="$(grep -P "\t$archivo\t" "$BASELINE" 2>/dev/null | head -1)"
		if [ -z "$base" ]; then
			base_estado=OK; base_fallas=0
		else
			base_estado="$(echo "$base" | cut -f1)"; base_fallas="$(echo "$base" | cut -f3)"
		fi
		local ahora antes
		ahora=$(peso "$estado" "${fallas/-/0}")
		antes=$(peso "$base_estado" "${base_fallas/-/0}")
		if [ "$ahora" -gt "$antes" ]; then
			echo "PEOR   $archivo: $base_estado ($base_fallas) -> $estado ($fallas) $resumen" >&2
			peor=$((peor + 1))
		elif [ "$ahora" -lt "$antes" ]; then
			echo "MEJOR  $archivo: $base_estado ($base_fallas) -> $estado ($fallas)"
			mejor=$((mejor + 1))
		fi
	done < "$RESULTADO"
	echo
	echo "$(grep -c '^OK' "$RESULTADO") de $(wc -l < "$RESULTADO" | tr -d ' ') archivos andan; $mejor mejoraron respecto de la línea base."
	if [ "$peor" -gt 0 ]; then
		echo "$peor archivos están peor que en la línea base. El detalle: app/tmp/tests/<archivo>.txt" >&2
		return 1
	fi
	echo "Sin regresiones."
	if [ "$mejor" -gt 0 ]; then
		echo "Bajá la línea base en el mismo PR: .github/ci/tests.sh --update"
	fi
}

case "${1:-}" in
	preparar) preparar ;;
	--update)
		correr .
		{
			echo "# Los archivos de test que hoy no andan en PHP 8.2, con su estado y cuántas fallas tienen."
			echo "# La escribe .github/ci/tests.sh --update; el CI falla si algo queda peor que acá."
			echo "# estado	archivo	fallas	resumen"
			grep -v '^OK' "$RESULTADO"
		} > "$BASELINE"
		echo "Línea base: $(grep -vc '^#' "$BASELINE") archivos que no andan."
		;;
	--) correr "${2:-.}" ;;
	*) correr . && comparar ;;
esac
