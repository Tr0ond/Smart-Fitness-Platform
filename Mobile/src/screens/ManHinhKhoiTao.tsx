import {StyleSheet, Text} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';

/** Xác nhận ứng dụng Member đã được khởi tạo, không gọi API. */
export default function ManHinhKhoiTao() {
  return (
    <SafeAreaView style={styles.khung}>
      <Text style={styles.tieuDe}>Smart Fitness Platform</Text>
      <Text style={styles.moTa}>
        Ứng dụng Member đã sẵn sàng để bắt đầu phát triển.
      </Text>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  khung: {
    flex: 1,
    justifyContent: 'center',
    padding: 24,
    backgroundColor: '#f6f8fa',
  },
  tieuDe: {
    fontSize: 26,
    fontWeight: '700',
    color: '#17212b',
  },
  moTa: {
    marginTop: 12,
    fontSize: 16,
    lineHeight: 24,
    color: '#475569',
  },
});
