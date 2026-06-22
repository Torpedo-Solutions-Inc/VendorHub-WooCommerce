#!/usr/bin/env bash
# VendorHub WooCommerce plugin — staging E2E verification helper.
# Run against a connected store after completing manual steps in TESTING.md.
set -euo pipefail

SITE_URL="${SITE_URL:-}"
API_TOKEN="${API_TOKEN:-}"
ORDER_ID="${ORDER_ID:-}"
API_BASE="${API_BASE:-https://api.vendorhub.app}"

pass=0
fail=0

check() {
	local label="$1"
	shift
	if "$@"; then
		echo "PASS: ${label}"
		pass=$((pass + 1))
	else
		echo "FAIL: ${label}"
		fail=$((fail + 1))
	fi
}

echo "=== VendorHub plugin E2E verification ==="
echo ""

echo "--- Production connect endpoint (HEAD) ---"
connect_code="$(curl -sS -o /dev/null -w '%{http_code}' "${API_BASE}/connect/woocommerce" || true)"
echo "GET ${API_BASE}/connect/woocommerce → HTTP ${connect_code}"
if [[ "${connect_code}" =~ ^(200|301|302|303|307|308)$ ]]; then
	echo "PASS: Connect route reachable"
	pass=$((pass + 1))
else
	echo "FAIL: Connect route not reachable (expected redirect or 200; got ${connect_code})"
	fail=$((fail + 1))
fi
echo ""

if [[ -z "${SITE_URL}" || -z "${API_TOKEN}" || -z "${ORDER_ID}" ]]; then
	echo "Skipping callback tests (set SITE_URL, API_TOKEN, ORDER_ID to run)."
	echo ""
	echo "Example:"
	echo "  SITE_URL=https://store.example API_TOKEN=xxx ORDER_ID=123 ./scripts/e2e-verify.sh"
	echo ""
	echo "=== Summary: ${pass} passed, ${fail} failed ==="
	exit $(( fail > 0 ? 1 : 0 ))
fi

callback_url="${SITE_URL%/}/wp-json/vendorhub/v1/order/${ORDER_ID}"
body='{"note":"VendorHub E2E verification note"}'
timestamp="$(date +%s000)"
signature="$(printf '%s.%s' "${timestamp}" "${body}" | openssl dgst -sha256 -hmac "${API_TOKEN}" | awk '{print $2}')"

echo "--- Callback (valid signature) ---"
valid_code="$(curl -sS -o /dev/null -w '%{http_code}' -X POST "${callback_url}" \
	-H "Authorization: Bearer ${API_TOKEN}" \
	-H "Content-Type: application/json" \
	-H "X-VendorHub-Timestamp: ${timestamp}" \
	-H "X-VendorHub-Signature: ${signature}" \
	-d "${body}")"
echo "POST ${callback_url} → HTTP ${valid_code}"
check "Valid callback accepted" test "${valid_code}" = "200"

echo ""
echo "--- Callback (invalid signature) ---"
bad_code="$(curl -sS -o /dev/null -w '%{http_code}' -X POST "${callback_url}" \
	-H "Authorization: Bearer ${API_TOKEN}" \
	-H "Content-Type: application/json" \
	-H "X-VendorHub-Timestamp: ${timestamp}" \
	-H "X-VendorHub-Signature: deadbeef" \
	-d "${body}")"
echo "POST ${callback_url} (bad sig) → HTTP ${bad_code}"
check "Invalid callback rejected" test "${bad_code}" = "401"

echo ""
echo "=== Summary: ${pass} passed, ${fail} failed ==="
exit $(( fail > 0 ? 1 : 0 ))
