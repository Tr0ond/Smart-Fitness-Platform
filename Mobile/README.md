# Smart Fitness Mobile

React Native CLI skeleton cho Member, ưu tiên Android. Đọc [PROJECT_RULES.md](../PROJECT_RULES.md) trước khi sửa code và [README gốc](../README.md) để chuẩn bị môi trường.

React Native 0.86.3, React 19.2.3, Community CLI 20.2.0; dùng TypeScript và safe-area-context theo template. Chỉ có màn hình khởi tạo trong `src/screens/`; chưa có nghiệp vụ, navigation, API hoặc store. Các thư mục dành sẵn được giữ bằng `.gitkeep`.

```powershell
npm ci
npm start
```

Trong terminal khác, sau khi cấu hình Android SDK/JDK và mở emulator hoặc kết nối thiết bị:

```powershell
npm run android
```

Kiểm tra skeleton:

```powershell
npm run typecheck
npm run lint
npm test -- --runInBand
npm ls --depth=0
```

Android project: `android/`, application ID `com.smartfitness`. Phiên bản SDK/Build Tools/NDK nằm trong `android/build.gradle`; không đưa `android/local.properties` lên Git. `ios/` được giữ từ template nhưng chưa kiểm tra. Debug keystore chỉ dùng phát triển, không dùng ký bản phát hành.

Đã kiểm tra dependency, TypeScript, ESLint, Jest, native config và Metro bundle Android. **Chưa build APK hoặc chạy ứng dụng trên thiết bị.** Xem README gốc để biết giới hạn môi trường và kết quả audit.
