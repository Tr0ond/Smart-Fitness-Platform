import {StatusBar} from 'react-native';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import ManHinhKhoiTao from './src/screens/ManHinhKhoiTao';

/** Hiển thị skeleton ứng dụng; chưa tích hợp nghiệp vụ. */
function App() {
  return (
    <SafeAreaProvider>
      <StatusBar barStyle="dark-content" backgroundColor="#f6f8fa" />
      <ManHinhKhoiTao />
    </SafeAreaProvider>
  );
}

export default App;
