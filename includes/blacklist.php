<?php
# enter permission:IP:Netmask
# deny  statements should be first
# but should work in any order
# if no deny statements are included, then allows are ignored also
# and all IPs will be allowed access
# if no allow statements are included, then all IPS except those
# in the deny statements are allowed access
#
# EXAMPLES
#   deny:192.168.10.0:255.255.255.0
#   allow:192.168.10.15:255.255.255.255
# will permit 192.168.10.15 only from that network
#
# to deny all
#   deny:255.255.255.255:255.255.255.255
# to allow all
#   allow:255.255.255.255:255.255.255.255
#
#deny:255.255.255.255:255.255.255.255
# put allow statements below here
#allow:255.255.255.255:255.255.255.255
# end blacklist.php
?>
