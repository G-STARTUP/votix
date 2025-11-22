# Auto Deployment – Hostinger Webhook

Webhook fourni:
```
https://webhooks.hostinger.com/deploy/82b6d0adfdf348ce4aa66aba8b677bb3
```

## 1. Pré‑requis
- Répertoire GitHub accessible (compte non suspendu). Actuellement: 403 push → résoudre suspension ou créer nouveau dépôt.
- Branch principale: `main` (ou celle attendue par Hostinger).
- Code prêt (README, sources, build config).

## 2. Ajout du Webhook GitHub (manuel)
Aller sur: `Settings > Webhooks > Add webhook` dans le dépôt.
1. Payload URL: coller l’URL Hostinger
2. Content type: `application/json`
3. Secret: générer une chaîne forte (ex: via 1Password) – si Hostinger supporte le secret (sinon laisser vide). Garder valeur dans `.env.local` hors repo.
4. SSL verification: laisser **Enable**.
5. Events: sélectionner "Just the push event" (suffisant pour déploiement automatique) ou inclure `pull_request` si besoin.
6. Active: coché.
7. Save.

## 3. Structure recommandée du dépôt
```
/ (racine)
  README.md
  .gitignore
  DEPLOYMENT_HOSTINGER.md
  src/ (code applicatif)
  build/ (artifacts générés, ignorés dans Git)
```

## 4. Workflow de push
1. Développer localement.
2. `git add .` / `git commit -m "feat: initial app"`
3. `git push origin main` → déclenche webhook (Hostinger récupère, build, déploie).

## 5. Sécurité
- Ne pas exposer secrets dans README.
- Activer branche protégée `main` (requête review avant merge) si équipe.
- Ajouter un secret Webhook pour valider signature (si Hostinger renvoie `X-Hub-Signature-256`).

## 6. Validation du déploiement
Après push, vérifier:
- Interface Hostinger: logs webhook (status 200). 
- Site en ligne reflète changements (vider cache navigateur si besoin).
- Si échec: vérifier payload delivery sous `Settings > Webhooks > Recent Deliveries`.

## 7. Logs & Debug
- Delivery: statut, codes HTTP, corps réponse.
- Si 404 ou 500: vérifier configuration côté Hostinger (chemin build, runtime).

## 8. Exemple payload GitHub (push)
GitHub envoie JSON incluant:
```json
{
  "ref":"refs/heads/main",
  "repository":{ "full_name":"G-STARTUP/votix" },
  "pusher": { "name":"<user>" },
  "commits":[ { "id":"<sha>", "message":"chore: initial commit" } ]
}
```
Hostinger déclenche script interne pour pull & déployer.

## 9. Alternative (Actions GitHub)
Si webhook indisponible, vous pouvez utiliser GitHub Actions:
```yaml
name: deploy
on: push
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build
        run: echo "Build step (npm ci && npm run build)" 
      - name: Deploy
        run: curl -X POST -H "Content-Type: application/json" -d '{"force":true}' https://webhooks.hostinger.com/deploy/82b6d0adfdf348ce4aa66aba8b677bb3
```
(À utiliser seulement si Hostinger accepte appels directs.)

## 10. Prochaines étapes
- Résoudre suspension du compte GitHub (403) ou migrer vers nouveau dépôt.
- Ajouter du code applicatif réel.
- Activer protection de branche.
- Optionnel: intégrer tests avant déploiement automatique.

## 11. Check rapide
- [ ] Webhook configuré
- [ ] Secret sauvegardé (si utilisé)
- [ ] Branch main protégée (optionnel)
- [ ] Push réussi sans 403
- [ ] Déploiement visible

## 12. FAQ
Q: Push ne déclenche rien? → Vérifier "Recent Deliveries" (statut Pending/Failed). 
Q: Webhook 403? → Vérifier IP GitHub autorisée côté Hostinger (si filtrage) ou secret mismatch.
Q: Multiples builds simultanés? → Mettre un système de lock côté serveur (fichier `.deploying`).

---
En cas de nouvelles contraintes Hostinger (clé API, repository privé), adapter ce document.
