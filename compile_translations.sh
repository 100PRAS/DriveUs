#!/bin/bash
# ============================================
# Script de compilation des fichiers .po en .mo
# Linux/Mac (avec gettext installé)
# ============================================

LOCALES_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/locales"
MSGFMT=$(command -v msgfmt)

if [ -z "$MSGFMT" ]; then
    echo ""
    echo "*** ERREUR: msgfmt non trouvé! ***"
    echo ""
    echo "Veuillez installer gettext:"
    echo "  Ubuntu/Debian: sudo apt-get install gettext"
    echo "  macOS: brew install gettext"
    echo ""
    exit 1
fi

echo "Compilation des fichiers .po en .mo..."
echo ""

# Compiler français
if [ -f "$LOCALES_DIR/fr_FR/LC_MESSAGES/messages.po" ]; then
    echo "[Compilation] Français (fr_FR)..."
    $MSGFMT -o "$LOCALES_DIR/fr_FR/LC_MESSAGES/messages.mo" "$LOCALES_DIR/fr_FR/LC_MESSAGES/messages.po"
    if [ $? -eq 0 ]; then
        echo "OK - messages.mo créé"
    else
        echo "ERREUR: Compilation française échouée!"
        exit 1
    fi
else
    echo "ERREUR: $LOCALES_DIR/fr_FR/LC_MESSAGES/messages.po non trouvé!"
fi
echo ""

# Compiler anglais
if [ -f "$LOCALES_DIR/en_US/LC_MESSAGES/messages.po" ]; then
    echo "[Compilation] Anglais (en_US)..."
    $MSGFMT -o "$LOCALES_DIR/en_US/LC_MESSAGES/messages.mo" "$LOCALES_DIR/en_US/LC_MESSAGES/messages.po"
    if [ $? -eq 0 ]; then
        echo "OK - messages.mo créé"
    else
        echo "ERREUR: Compilation anglaise échouée!"
        exit 1
    fi
else
    echo "ERREUR: $LOCALES_DIR/en_US/LC_MESSAGES/messages.po non trouvé!"
fi
echo ""

echo "============================================"
echo "Compilation terminée avec succès!"
echo ""
echo "Fichiers générés:"
echo "- $LOCALES_DIR/fr_FR/LC_MESSAGES/messages.mo"
echo "- $LOCALES_DIR/en_US/LC_MESSAGES/messages.mo"
echo ""
echo "Vous pouvez maintenant utiliser _('texte') dans votre code PHP!"
echo "============================================"
