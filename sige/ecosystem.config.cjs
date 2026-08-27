module.exports = {
  apps: [{
    name: 'sige-php',
    script: '/home/user/webapp/sige/start_server.sh',
    watch: false,
    instances: 1,
    exec_mode: 'fork',
  }]
}
