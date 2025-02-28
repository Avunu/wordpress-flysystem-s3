{ pkgs ? import <nixpkgs> {} }:

pkgs.mkShell {
  name = "wordpress-flysystem-s3-dev";

  buildInputs = with pkgs; [
    php83                   # PHP 8.4
    php83Packages.composer  # Composer for managing PHP dependencies
    nodejs                  # Node.js (optional, for frontend tasks if needed)
  ];

  shellHook = ''
    echo "Development environment for wordpress-flysystem-s3 is ready."
    echo "Use 'composer install' to install PHP dependencies."
    echo "Use 'npx @wp-now/wp-now start --wp=latest --php=8.3' to start a local WordPress instance."
  '';
}
