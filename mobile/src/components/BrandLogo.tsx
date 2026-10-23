import { useThemedStyles } from '../theme/ThemeProvider';
import { Image, StyleSheet, View } from 'react-native';

const brandLogo = require('../../assets/img/edlink-logo.png');

export function BrandLogo({ large = false, compact = false }: { large?: boolean; compact?: boolean }) {
  const styles = useThemedStyles(baseStyles);
  return (
    <View style={styles.lockup}>
      <Image accessibilityLabel="Edlink" source={brandLogo} style={[styles.image, large && styles.imageLarge, compact && styles.imageCompact]} resizeMode="contain" />
    </View>
  );
}

const baseStyles = StyleSheet.create({
  lockup: { alignItems: 'center' },
  image: { width: 190, height: 118 },
  imageLarge: { width: 310, height: 215 },
  imageCompact: { width: 132, height: 70 },
});

const styles = baseStyles;
