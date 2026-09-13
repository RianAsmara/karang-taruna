# Building the Android APK

Distribution is a shared APK, not the Play Store: the chair sends the file
or a download link to the same WhatsApp group the organization already
uses. No store review, no wait, and it matches how these groups already
share things.

```bash
npm install -g eas-cli
eas login
eas build --platform android --profile production
```

EAS returns a download URL when the build finishes. Share that link.

## Before the first build

- `EXPO_PUBLIC_API_URL` in `eas.json` must point at the production API
  over **HTTPS**. It is inlined at build time — changing it later means a
  new build, not a new release of the same one.
- `android.package` in `app.json` is the app's permanent identity. It
  cannot change once people have the app installed without every one of
  them uninstalling first.

## Installing a shared APK

Android blocks installs from outside the Play Store by default. The person
installing has to allow it once, per app:

> Settings → Apps → Special app access → Install unknown apps → (the app
> they are downloading with, usually WhatsApp or Chrome) → Allow

Say this in the same message as the link. Without it the install fails
with a message that explains nothing, and people give up there.

## Updating

An APK does not auto-update. Every release means sending a new link and
asking people to install it over the old one. Keep releases infrequent and
say what changed.

## Local development build

`npx expo run:android` (needs Android Studio) rebuilds the native project
— required after adding any native module or changing `app.json`. A Metro
reload is not enough for either.
