server "your-staging-server-ip", user: "deploy", roles: %w{app db web}

# SSH configuration options
set :ssh_options, {
  keys: %w(~/.ssh/id_rsa),
  forward_agent: true,
  auth_methods: %w(publickey)
}
