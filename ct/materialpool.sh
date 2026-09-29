#!/usr/bin/env bash
# Beim Start per curl kennt der Community-Core das Quellrepository nicht.
COMMUNITY_SCRIPTS_URL="${COMMUNITY_SCRIPTS_URL:-https://raw.githubusercontent.com/stevenbuehner/materialpool/master}"
export COMMUNITY_SCRIPTS_URL
_cs_boot="${COMMUNITY_SCRIPTS_CORE_DIR:-$(dirname "${BASH_SOURCE[0]}")/../../core}/core/build.func"
# shellcheck source=/dev/null
source "$_cs_boot" 2>/dev/null || source <(curl -fsSL "${COMMUNITY_SCRIPTS_CORE_URL:-https://raw.githubusercontent.com/community-scripts/core/main}/core/build.func")

APP="Materialpool"
var_tags="${var_tags:-laravel;resources}"
var_cpu="${var_cpu:-2}"
var_ram="${var_ram:-3072}"
var_disk="${var_disk:-24}"
var_os="${var_os:-debian}"
var_version="${var_version:-13}"
var_arm64="${var_arm64:-no}"
var_unprivileged="${var_unprivileged:-1}"

header_info "$APP"
variables
color
catch_errors

function update_script() {
  [[ -x /usr/local/sbin/materialpool-update ]] || { msg_error "Materialpool-Updater fehlt"; exit 1; }
  /usr/local/sbin/materialpool-update update
}

start
build_container
description
msg_ok "Materialpool-LXC wurde erstellt."
echo -e "${INFO}${YW}HTTP: ${GN}http://${IP}:80${CL}"
