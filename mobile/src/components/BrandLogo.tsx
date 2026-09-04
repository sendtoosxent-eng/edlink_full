import { Image, StyleSheet, View } from 'react-native';

const brandLogo = require('../../assets/img/edlink-logo.png');

export function BrandLogo({ large = false }: { large?: boolean }) {
  return (
    <View style={styles.lockup}>
      <Image accessibilityLabel="Edlink" source={brandLogo} style={[styles.image, large && styles.imageLarge]} resizeMode="contain" />
    </View>
  );
}

const styles = StyleSheet.create({
  lockup: { alignItems: 'center' },
  image: { width: 190, height: 118 },
  imageLarge: { width: 310, height: 215 },
});
