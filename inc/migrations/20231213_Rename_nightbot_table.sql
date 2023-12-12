ALTER TABLE nq_owner_nightbot
  RENAME COLUMN client_id TO nb_client_id,
  RENAME COLUMN client_secret TO nb_client_secret,
  RENAME COLUMN token TO nb_token,
  RENAME COLUMN token_expires TO nb_token_expires,
  RENAME COLUMN refresh_token TO nb_refresh_token;

RENAME TABLE nq_owner_nightbot TO nq_owner_platform_auth;

ALTER TABLE nq_owner_platform_auth
RENAME KEY nq_owr_nightbot_owner_uq TO nq_pltf_auth_owner_uq;
